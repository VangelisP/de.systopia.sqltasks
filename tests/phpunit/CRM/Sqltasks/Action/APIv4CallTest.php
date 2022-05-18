<?php

use \Civi\Api4 as Api4;

/**
 * Test APIv4Call Action
 *
 * @group headless
 */
class CRM_Sqltasks_Action_APIv4CallTest extends CRM_Sqltasks_Action_AbstractActionTest {

  public function testAPIv4Call() {
    $tmpContactTable = 'tmp_test_action_apiv4call';
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $contactID = self::createRandomTestContact();

      $tableRows[] = [
        'contact_id' => $contactID,
        'exclude'    => $i === 2 ? 1 : 0,
      ];

      $contactResult = Api4\Contact::get()
        ->addSelect('do_not_email')
        ->addWhere('id', '=', $contactID)
        ->setLimit(1)
        ->execute();

      // Make sure do_not_email is set to FALSE for every contact before the task is executed
      $this->assertFalse(
        $contactResult[0]['do_not_email'],
        'The DO_NOT_EMAIL flag should not be set'
      );
    }

    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'       => 'CRM_Sqltasks_Action_APIv4Call',
          'table'      => $tmpContactTable,
          'enabled'    => TRUE,
          'entity'     => 'Contact',
          'action'     => 'update',
          'parameters' => json_encode([
            'where' => [
              ['id', '=', '$data.contact_id'],
            ],
            'values' => [
              'do_not_email' => TRUE,
            ],
            'limit' => 1,
          ]),
        ],
        self::getDropTempContactTableAction($tmpContactTable),
      ],
    ];

    $this->createAndExecuteTask($config);

    foreach ($tableRows as $row) {
      $contactID = $row['contact_id'];
      $exclude = (bool) $row['exclude'];

      $contactResult = Api4\Contact::get()
        ->addSelect('do_not_email')
        ->addWhere('id', '=', $contactID)
        ->setLimit(1)
        ->execute();

      $this->assertEquals(
        !$exclude,
        $contactResult[0]['do_not_email'],
        'The DO_NOT_EMAIL flag should' . ($exclude ? ' not ' : ' ') . 'be set'
      );
    }
  }

  public function testHandleErrors() {
    $tmpContactTable = 'tmp_test_action_apiv4call';
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $tableRows[] = [
        'contact_id' => $i,
        'exclude'    => 0,
      ];
    }

    $rowCount = count($tableRows);

    foreach (['log_only', 'report_error_and_continue', 'report_error_and_abort'] as $errorHandling) {
      $config = [
        'version' => CRM_Sqltasks_Config_Format::CURRENT,
        'actions' => [
          self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
          [
            'type'              => 'CRM_Sqltasks_Action_APIv4Call',
            'table'             => $tmpContactTable,
            'enabled'           => TRUE,
            'handle_api_errors' => $errorHandling,
            'entity'            => 'Contact',
            'action'            => 'no_such_action',
            'parameters'        => json_encode([]),
          ],
          self::getDropTempContactTableAction($tmpContactTable),
        ],
      ];

      $task = $this->createAndExecuteTask($config);

      switch ($errorHandling) {
        case 'log_only': {
          $this->assertLogContains("0 API call(s) successfull.");

          $this->assertLogContains(
            "$rowCount API call(s) FAILED with message: 'Api Contact no_such_action version 4 does not exist.'"
          );

          $this->assertFalse(
            $task->hasExecutionErrors(),
            'Execution errors should not have been reported'
          );

          break;
        }

        case 'report_error_and_continue': {
          $this->assertLogContains("0 API call(s) successfull.");

          $this->assertLogContains(
            "$rowCount API call(s) FAILED with message: 'Api Contact no_such_action version 4 does not exist.'"
          );

          $this->assertTrue(
            $task->hasExecutionErrors(),
            'Execution errors should have been reported'
          );

          break;
        }

        case 'report_error_and_abort': {
          $this->assertLogContains("0 API call(s) successfull.");

          $this->assertLogContains(
            "1 API call(s) FAILED with message: 'Api Contact no_such_action version 4 does not exist.'"
          );

          $expectedSkipped = $rowCount - 1;

          $this->assertLogContains("$expectedSkipped API call(s) SKIPPED due to previous error.");

          $this->assertTrue(
            $task->hasExecutionErrors(),
            'Execution errors should have been reported'
          );

          break;
        }
      }
    }
  }

  public function testStoreResults() {
    $tmpContactTable = 'tmp_test_action_apiv4call';
    $tableRows = [];

    for ($i = 0; $i < 3; $i++) {
      $contactID = self::createRandomTestContact();

      $tableRows[] = [
        'contact_id' => $contactID,
        'exclude'    => 0,
      ];
    }

    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'              => 'CRM_Sqltasks_Action_APIv4Call',
          'table'             => $tmpContactTable,
          'enabled'           => TRUE,
          'store_api_results' => TRUE,
          'entity'            => 'Contact',
          'action'            => 'get',
          'parameters'        => json_encode([
            'select' => ['contact_type'],
            'where' => [
              ['id', '=', '$data.contact_id'],
            ],
            'limit' => 1,
          ]),
        ],
      ],
    ];

    $this->createAndExecuteTask($config);

    $query = CRM_Core_DAO::executeQuery(
      "SELECT `contact_id`, `sqltask_api_result` FROM `$tmpContactTable`"
    );

    while ($query->fetch()) {
      $this->assertObjectHasAttribute(
        'sqltask_api_result',
        $query,
        'Temporary table should have a sqltask_api_result column'
      );

      $this->assertNotNull(
        $query->sqltask_api_result,
        'Field sqltask_api_result should not be null'
      );

      $apiResult = json_decode($query->sqltask_api_result, TRUE);

      $this->assertEquals(
        'Individual',
        $apiResult[0]['contact_type'],
        'API result should contain the contact type "Individual"'
      );
    }

    $query->free();
  }

  public function testInputValues() {
    $tmpContactTable = 'tmp_test_action_apiv4call';

    $tableRows = [
      [
        // Placeholder entry
        'contact_id' => 0,
        'exclude'    => 0,
      ]
    ];

    $contactID = self::createRandomTestContact();

    $contactResult = Api4\Contact::get()
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactID)
      ->setLimit(1)
      ->execute();

    $expectedDisplayName = $contactResult[0]['display_name'];

    $config = [
      'version'        => CRM_Sqltasks_Config_Format::CURRENT,
      'input_required' => TRUE,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'              => 'CRM_Sqltasks_Action_APIv4Call',
          'table'             => $tmpContactTable,
          'enabled'           => TRUE,
          'store_api_results' => TRUE,
          'entity'            => 'Contact',
          'action'            => 'get',
          'parameters'        => json_encode([
            'select' => ['display_name'],
            'where'  => [
              // Take the contact ID from the input value instead of the temp table
              ['id', '=', '$context.input_val'],
            ],
            'limit'  => 1,
          ]),
        ],
      ],
    ];

    $this->createAndExecuteTask(
      $config,
      [ 'input_val' => $contactID ]
    );

    $query = CRM_Core_DAO::executeQuery(
      "SELECT `sqltask_api_result` FROM `$tmpContactTable` LIMIT 1"
    );

    $query->fetch();
    $actionResult = json_decode($query->sqltask_api_result, TRUE);

    $this->assertEquals($expectedDisplayName, $actionResult[0]['display_name']);
  }

  public function testGlobalTokens() {
    $tmpContactTable = 'tmp_test_action_apiv4call';

    $tableRows = [
      [
        // Placeholder entry
        'contact_id' => 0,
        'exclude'    => 0,
      ]
    ];

    $contactID = self::createRandomTestContact();
    CRM_Sqltasks_GlobalToken::singleton()->setValue('contact_id', $contactID);

    $contactResult = Api4\Contact::get()
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactID)
      ->setLimit(1)
      ->execute();

    $expectedDisplayName = $contactResult[0]['display_name'];

    $config = [
      'version'        => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'              => 'CRM_Sqltasks_Action_APIv4Call',
          'table'             => $tmpContactTable,
          'enabled'           => TRUE,
          'store_api_results' => TRUE,
          'entity'            => 'Contact',
          'action'            => 'get',
          'parameters'        => json_encode([
            'select' => ['display_name'],
            'where'  => [
              // Take the contact ID from the input value instead of the temp table
              ['id', '=', '$config.contact_id'],
            ],
            'limit'  => 1,
          ]),
        ],
      ],
    ];

    $this->createAndExecuteTask($config);

    $query = CRM_Core_DAO::executeQuery(
      "SELECT `sqltask_api_result` FROM `$tmpContactTable` LIMIT 1"
    );

    $query->fetch();
    $actionResult = json_decode($query->sqltask_api_result, TRUE);

    $this->assertEquals($expectedDisplayName, $actionResult[0]['display_name']);
  }

  public function testSettings() {
    $tmpContactTable = 'tmp_test_action_apiv4call';
    $contactID = self::createRandomTestContact();

    $tableRows = [
      [
        'contact_id' => $contactID,
        'exclude'    => 0,
      ]
    ];

    $maxAttachments = Civi::settings()->get('max_attachments');

    $config = [
      'version' => CRM_Sqltasks_Config_Format::CURRENT,
      'actions' => [
        self::getCreateTempContactTableAction($tmpContactTable, $tableRows),
        [
          'type'              => 'CRM_Sqltasks_Action_APIv4Call',
          'table'             => $tmpContactTable,
          'enabled'           => TRUE,
          'store_api_results' => TRUE,
          'entity'            => 'Note',
          'action'            => 'create',
          'parameters'        => json_encode([
            'values' => [
              'entity_table' => 'civicrm_contact',
              'entity_id'    => $contactID,
              'note'         => '$setting.max_attachments',
            ],
          ]),
        ],
        self::getDropTempContactTableAction($tmpContactTable),
      ],
    ];

    $this->createAndExecuteTask($config);

    $noteResult = Api4\Note::get()
      ->addSelect('note')
      ->addWhere('entity_table', '=', 'civicrm_contact')
      ->addWhere('entity_id', '=', $contactID)
      ->setLimit(1)
      ->execute();

    $this->assertEquals($maxAttachments, $noteResult[0]['note']);
  }
}

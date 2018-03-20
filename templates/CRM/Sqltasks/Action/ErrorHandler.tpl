{*-------------------------------------------------------+
| SYSTOPIA SQL TASKS EXTENSION                           |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

<div class="sql-tasks">
  <div id="help" style="margin-left: 5px; margin-right: 5px;">
    {ts domain="de.systopia.sqltasks"}This action will be triggered if the execution of the task has reported errors, or has failed completely.{/ts}
  </div>

  <div class="crm-section">
    <div class="label">{$form.error_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Error Table{/ts}", {literal}{"id":"id-handler-error-table","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.error_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.error_drop_table.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Drop Error Table{/ts}", {literal}{"id":"id-handler-error-table-drop","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.error_drop_table.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.error_email.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Email{/ts}", {literal}{"id":"id-handler-email","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.error_email.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.error_email_template.label}</div>
    <div class="content">{$form.error_email_template.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.error_attach_log.label}&nbsp;<a onclick='CRM.help("{ts domain="de.systopia.sqltasks"}Attach Log{/ts}", {literal}{"id":"id-handler-attach-log","file":"CRM\/Sqltasks\/Action\/ResultHandler"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.sqltasks"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.error_attach_log.html}</div>
    <div class="clear"></div>
  </div>
</div>

{* HEADER *}

<div class="crm-submit-buttons">
  <hr>
</div>
{assign var=i value=0}
<table>
  {foreach from=$elementNames item=elementName}
  {assign var=m4 value=$i%2}
  {assign var=m2 value=$i%2}
  {if $m4 eq 0}
  </tr><tr class="crm-section">
    {/if}
    <td class="right"> {$form.$elementName.label}</td>
    <td>{$form.$elementName.html}
      {assign var=i value=$i+1}
      {/foreach}
</table>
{*    {debug}*}
{* FOOTER *}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

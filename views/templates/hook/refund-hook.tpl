{*
*
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * It is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author Buckaroo.nl <plugins@buckaroo.nl>
 * @copyright Copyright (c) Buckaroo B.V. 
 * @license   http://opensource.org/licenses/afl-3.0 Academic Free License (AFL 3.0)
*}
<div id="formAddPaymentPanel" class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i>
        {l s='Buckaroo payments & refunds' mod='buckaroo3'} <span
                class="badge">{$order->getOrderPayments()|@count|escape:'quotes':'UTF-8'}</span>
    </div>
    {if $messages != ''}
        <script>
            $(".bootstrap").after(function () {
                {if $messageStatus == 0}
                return "<div class='alert alert-danger'>{$messages|escape:'html':'UTF-8'}</div>";
                {else}
                return "<div class='alert alert-success'>{$messages|escape:'html':'UTF-8'}</div>";
                {/if}
            });

        </script>
    {/if}
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th><span class="title_box ">{l s='Date' mod='buckaroo3'}</span></th>
                <th><span class="title_box ">{l s='Payment method' mod='buckaroo3'}</span></th>
                <th><span class="title_box ">{l s='Transaction ID' mod='buckaroo3'}</span></th>
                <th><span class="title_box ">{l s='Amount' mod='buckaroo3'}</span></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$payments item=payment}
                <tr>
                    <td>{dateFormat date=$payment->date_add full=true}</td>
                    <td>{$payment->payment_method|escape:'html':'UTF-8'}</td>
                    <td>{$payment->transaction_id|escape:'html':'UTF-8'}</td>
                    <td>{displayPrice price=$payment->amount currency=$payment->id_currency}</td>
                    <td class="actions">
                        {if $payment->amount > 0 && $paymentInfo[$payment->id]["refunded"] == 0}
                            <button class="btn btn-default open_payment_information">
                                <i class="icon-search"></i>
                                {l s='Details' mod='buckaroo3'}
                            </button>
                        {elseif $paymentInfo[$payment->id]["refunded"] * (-1) == $payment->amount}
                            Fully refunded
                        {elseif $paymentInfo[$payment->id]["refunded"] * (-1) < $payment->amount}
                            Partially refunded
                        {else}
                            Refund transaction
                        {/if}
                    </td>
                </tr>
                <tr class="payment_information" style="display: none;">
                    <td colspan="4">
                        {if $payment->amount > 0 && $payment->transaction_id}
                            <a style="width: 190px"
                               onclick="return confirm('Are you sure want to refund {$payment->amount|escape:'htmlall':'UTF-8'} ?')"
                               class="btn btn-primary btn-block"
                               href="?controller=AdminRefund&action=refund&transaction_id={$payment->transaction_id|escape:'html':'UTF-8'}&id_order={$order->id|escape:'html':'UTF-8'}&token={getAdminToken tab='AdminRefund'}&admtoken={getAdminToken tab='AdminOrders'}">Refund</a>
                        {else}
                            Transaction can't be refunded
                        {/if}
                    </td>
                </tr>
                {foreachelse}
                <tr>
                    <td class="list-empty hidden-print" colspan="5">
                        <div class="list-empty-msg">
                            <i class="icon-warning-sign list-empty-icon"></i>
                            {l s='No payment methods are available' mod='buckaroo3'}
                        </div>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>
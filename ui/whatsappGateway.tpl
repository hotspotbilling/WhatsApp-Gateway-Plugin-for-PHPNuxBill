{include file="sections/header.tpl"}

{if $menu == 'login'}

    <form class="form" method="post" role="form">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Login Whatsapp
                        </h3>
                    </div>
                    <div class="box-body with-border text-center">
                        {$message}
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-xs-4">
                                <a class="btn btn-default btn-sm btn-block" href="{$_url}plugin/whatsappGateway">back</a>
                            </div>
                            <div class="col-xs-8">
                                <button class="btn btn-primary btn-sm btn-block" type="submit">Check Login</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
{elseif $menu == 'config'}

    <form class="form" method="post" role="form" action="{$_url}plugin/whatsappGateway_config">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Configuration
                        </h3>
                    </div>
                    <div class="box-body with-border">
                        <div class="form-group">
                            <label>Server URL</label>
                            <input type="text" class="form-control" name="whatsapp_gateway_url"
                                value="{$_c['whatsapp_gateway_url']}" required placeholder="http://localhost:3000">
                            <a href="https://github.com/dimaskiddo/go-whatsapp-multidevice-rest" class="pull-right"
                                target="_blank">Go WhatsApp Multi-Device</a>
                        </div>
                        <div class="form-group">
                            <label>Auth Basic Password</label>
                            <input type="text" class="form-control" name="whatsapp_gateway_secret" required
                                placeholder="AUTH_BASIC_PASSWORD" value="{$_c['whatsapp_gateway_secret']}">
                            <span class="text-muted">AUTH_BASIC_PASSWORD From .env, change this will change secret for API</span>
                        </div>
                        <div class="form-group">
                            <label class="control-label">{Lang::T('Country Code Phone')}</label>
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon1">+</span>
                                <input type="text" class="form-control" id="whatsapp_country_code_phone" placeholder="62"
                                    name="whatsapp_country_code_phone" value="{$_c['whatsapp_country_code_phone']}">
                            </div>
                            <span class="text-muted">if you put 62, Phone started with 0xxxx will change to 62xxxx</span>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-xs-4">
                                <a class="btn btn-default btn-sm btn-block" href="{$_url}plugin/whatsappGateway">back</a>
                            </div>
                            <div class="col-xs-8">
                                <button class="btn btn-primary btn-sm btn-block" type="submit">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
{else}
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="glyphicon glyphicon-plus"></i>
                        Add Phone
                    </h3>
                    <div class="box-tools pull-right">
                        <a href="{$_url}plugin/whatsappGateway_config" class="btn btn-box-tool" data-toggle="tooltip"
                            data-placement="top" title="Configuration"><i class="glyphicon glyphicon-cog"></i></a>
                    </div>
                </div>
                <div class="box-body with-border">
                    <form class="form-horizontal" method="post" role="form" action="{$_url}plugin/whatsappGateway_addPhone">
                        <div class="form-group">
                            <div class="col-md-12">
                                <label>{Lang::T('Phone Number')}</label>
                                <div class="input-group">
                                    <span class="input-group-addon" id="basic-addon1"><i
                                            class="glyphicon glyphicon-phone"></i></span>
                                    <input type="text" class="form-control" name="phonenumber" required
                                        placeholder="{$_c['country_code_phone']} {Lang::T('Phone Number')}">
                                </div>
                                <span class="pull-right">Use Country Code as whatsapp need it</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-12">
                                <button class="btn btn-success btn-block btn-sm" type="submit">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Registered Phone</h3>
                    <div class="box-tools pull-right">
                        <a href="{$_url}plugin/whatsappGateway_config" class="btn btn-box-tool" data-toggle="tooltip"
                            data-placement="top" title="Configuration"><i class="glyphicon glyphicon-cog"></i></a>
                    </div>
                </div>
                <table class="table table-condensed table-bordered">
                    <thead>
                        <tr>
                            <th>Phone Number</th>
                            <th>Status</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $phones as $phone}
                            <tr>
                                <td>{$phone}</td>
                                <td api-get-text='{$_url}plugin/whatsappGateway_status&p={$phone}'><span
                                        class="label label-default">&nbsp;</span></td>
                                <td>
                                    <a href="{$_url}plugin/whatsappGateway_login&p={$phone}"
                                        class="btn btn-xs btn-primary">QRCode</a>
                                    <a href="{$_url}plugin/whatsappGateway_login&p={$phone}&pair"
                                        class="btn btn-xs btn-primary">Paircode</a>
                                </td>
                                <td>
                                    <a href="{$_url}plugin/whatsappGateway_delPhone&p={$phone}" class="btn btn-xs btn-danger"
                                        onclick="return confirm('Remove {$phone}?')">Remove</a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="bs-callout bs-callout-warning well">
                <h4>API To send directly</h4>
                <input type="text" class="form-control" readonly onclick="this.select();"
                    value="{$_url}plugin/whatsappGateway_send&to=[number]&msg=[text]&secret={md5($_c['whatsapp_gateway_secret'])}">
                <span class="text-muted">Change Auth Basic Password will change secret. No need to change whatsapp URL in PHPNuxBill with this. the plugin will work directly.</span>
            </div>
        </div>
    </div>
{/if}

<div class="bs-callout bs-callout-warning well">
    <h4>Sending WhatsApp</h4>
    <p>If you put multiple number, it will send random to any existed phone number. even if it not logged in to
        WhatsApp.</p>
    <p><b>Empty Whatsapp Server URL in PHPNuxBill configuration</b>, this plugin will overide sending WhatsApp.</p>
    <p>This plugin only support <a href="https://github.com/dimaskiddo/go-whatsapp-multidevice-rest" target="_blank">Go
            WhatsApp Multi-Device</a>
</div>
{include file="sections/footer.tpl"}
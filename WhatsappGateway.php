<?php

register_menu("Whatsapp Gateway", true, "whatsappGateway", 'AFTER_SETTINGS', 'glyphicon glyphicon-comment', '', '', ['Admin', 'SuperAdmin']);

register_hook('send_whatsapp', 'whatsappGateway_hook_send_whatsapp');

function whatsappGateway()
{
    global $ui, $config, $admin;
    _admin();
    $path = whatsappGateway_getPath();

    if (empty($config['whatsapp_gateway_secret'])) {
        r2(U . 'plugin/whatsappGateway_config', 'e', 'Please configure first');
    }

    $files = scandir($path);
    $phones = [];
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'nux') {
            $phone = str_replace(".nux", "", $file);
            $phones[] = $phone;
        }
    }

    $ui->assign('phones', $phones);
    $ui->assign('_title', 'Whatsap Gateway');
    $ui->assign('_system_menu', 'plugin/whatsappGateway');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    $ui->display('whatsappGateway.tpl');
}

function whatsappGateway_config()
{
    global $ui;
    _admin();

    if (!empty(_post('whatsapp_gateway_url')) || !empty(_post('whatsapp_gateway_secret'))) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'whatsapp_gateway_url')->find_one();
        if ($d) {
            $d->value = _post('whatsapp_gateway_url');
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'whatsapp_gateway_url';
            $d->value = _post('whatsapp_gateway_url');
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'whatsapp_gateway_secret')->find_one();
        if ($d) {
            $d->value = _post('whatsapp_gateway_secret');
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'whatsapp_gateway_secret';
            $d->value = _post('whatsapp_gateway_secret');
            $d->save();
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'whatsapp_country_code_phone')->find_one();
        if ($d) {
            $d->value = _post('whatsapp_country_code_phone');
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'whatsapp_country_code_phone';
            $d->value = _post('whatsapp_country_code_phone');
            $d->save();
        }
        r2(U . 'plugin/whatsappGateway_config', 's', 'Configuration saved');
    }
    $ui->assign('_title', 'Whatsap Gateway Configuration');
    $ui->assign('_system_menu', 'plugin/whatsappGateway');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    $ui->assign('menu', 'config');
    $ui->display('whatsappGateway.tpl');
}


function whatsappGateway_login()
{
    global $ui;
    _admin();

    $phone = alphanumeric(_get('p'));
    $path = whatsappGateway_getPath();
    if (empty($phone)) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Not Found');
    }
    if (!file_exists("$path$phone.nux")) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Not Found.');
    }
    $json = json_decode(file_get_contents("$path$phone.nux"), true);
    if (!isset($json['jwt'])) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Not Connected');
    }
    if ($json['jwt'] == '') {
        r2(U . 'plugin/whatsappGateway', 'e', 'Not Connected.');
    }
    if (strlen($json['jwt']) > 4 && substr($json['jwt'], 0, 5) == 'Error') {
        // repeat request
        $json['jwt'] = whatsappGateway_getJwtApi($phone);
        if (strlen($json['jwt']) > 4 && substr($json['jwt'], 0, 5) == 'Error') {
            r2(U . 'plugin/whatsappGateway', 'e', $json['jwt']);
        } else {
            file_put_contents("$path$phone.nux", json_encode($json));
        }
    }

    $result = whatsappGateway_loginApi($json['jwt']);
    if (strlen($result)) {
        if (substr($result, 0, 1) == '{') {
            $json = json_decode($result, true);
            if (!empty($json['data']['paircode'])) {
                $message = $json['message'] . '<br><br>';
                $message .= '<h1>' . $json['data']['paircode'] . '</h1><br>';
                $message .= 'Timeout in ' . $json['data']['timeout'] . ' Second(s)<br>';
            } else {
                $message = $json['message'];
            }
        } else {
            $message = $result;
        }
    } else {
        $message = $result;
    }
    if (trim($message) == 'WhatsApp Client is Reconnected') {
        $message = '<span class="label label-success">Logged in</span>';
    }
    $ui->assign('message', $message);
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    $ui->assign('menu', 'login');
    $ui->display('whatsappGateway.tpl');
}

function whatsappGateway_addPhone()
{
    _admin();
    $path = whatsappGateway_getPath();
    $phone = alphanumeric(_post("phonenumber"));
    if (empty($phone)) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone not found');
    }
    if (file_exists("$path$phone.nux")) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone already exists');
    }
    $json['jwt'] = whatsappGateway_getJwtApi($phone);
    file_put_contents("$path$phone.nux", json_encode($json));
    if (file_exists("$path$phone.nux")) {
        r2(U . 'plugin/whatsappGateway', 's', 'Phone Added');
    } else {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone Failed to add');
    }
}

function whatsappGateway_send()
{
    global $config;
    $to = alphanumeric(_req('to'));
    $msg = _req('msg');
    $secret = _req('secret');
    if ($secret != md5($config['whatsapp_gateway_secret'])) {
        showResult(false, 'Invalid secret');
    }
    $result  = whatsappGateway_hook_send_whatsapp([$to, $msg]);
    $json = json_decode($result, true);
    if ($json) {
        showResult(true, '', $json);
    } else {
        showResult(false, '', $result);
    }
}

function whatsappGateway_hook_send_whatsapp($data = [])
{
    global $config;
    list($phone, $txt) = $data;
    if (!empty($config['whatsapp_gateway_url'])) {
        if (!empty($config['whatsapp_country_code_phone'])) {
            $phone = whatsappGateway_phoneFormat($phone);
        }
        $path = whatsappGateway_getPath();
        $files = scandir($path);
        $was = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'nux') {
                $was[] = $file;
            }
        }
        $wa = $was[rand(0, count($was) - 1)];
        $json = json_decode(file_get_contents("$path$wa"), true);
        $url = $config['whatsapp_gateway_url'];
        return Http::postData(
            $url . '/send/text',
            array('msisdn' => $phone, 'message' => $txt),
            [
                'Content-Type: application/x-www-form-urlencoded',
                "Authorization: Bearer $json[jwt]"
            ]
        );
    }
}

function whatsappGateway_delPhone()
{
    _admin();
    $path = whatsappGateway_getPath();
    $phone = alphanumeric(_get('p'));
    if (empty($phone)) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone not found');
    }
    if (!file_exists("$path$phone.nux")) {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone not exists');
    }
    if (unlink("$path$phone.nux")) {
        r2(U . 'plugin/whatsappGateway', 's', 'Phone Deleted');
    } else {
        r2(U . 'plugin/whatsappGateway', 'e', 'Phone Failed to Delete');
    }
}

function whatsappGateway_status()
{
    $phone = alphanumeric(_get('p'));
    $path = whatsappGateway_getPath();
    if (empty($phone)) {
        die('<span class="label label-danger">Not Found</span>');
    }
    if (!file_exists("$path$phone.nux")) {
        die('<span class="label label-danger">Not Found.</span>');
    }

    $json = json_decode(file_get_contents("$path$phone.nux"), true);
    if (!isset($json['jwt'])) {
        die('<span class="label label-danger">Not Connected</span>');
    }
    if ($json['jwt'] == '') {
        die('<span class="label label-danger">Not Connected.</span>');
    }
    if (strlen($json['jwt']) > 4 && substr($json['jwt'], 0, 5) == 'Error') {
        // repeat request
        $json['jwt'] = whatsappGateway_getJwtApi($phone);
        if (strlen($json['jwt']) > 4 && substr($json['jwt'], 0, 5) == 'Error') {
            die('<span class="label label-danger">' . $json['jwt'] . '</span>');
        } else {
            file_put_contents("$path$phone.nux", json_encode($json));
        }
    }

    $result = whatsappGateway_loginApi($json['jwt']);
    if (strlen($result)) {
        if (substr($result, 0, 1) == '{') {
            $json = json_decode($result, true);
            if (!empty($json['data']['paircode'])) {
                $message = $json['message'] . '<br><br>';
                $message .= '<h1>' . $json['data']['paircode'] . '</h1><br>';
                $message .= 'Timeout in ' . $json['data']['timeout'] . ' Second(s)<br>';
            } else {
                $message = $json['message'];
            }
        } else {
            $message = $result;
        }
    } else {
        $message = $result;
    }
    if (trim($message) == 'WhatsApp Client is Reconnected') {
        die('<span class="label label-success">Logged in</span>');
    } else {
        die('<span class="label label-danger">Not Logged in</span>');
    }
    die();
}

function whatsappGateway_getPath()
{
    global $UPLOAD_PATH;
    $path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "whatsapp" . DIRECTORY_SEPARATOR;
    if (!file_exists($path)) {
        mkdir($path);
    }
    return $path;
}


function whatsappGateway_getJwtApi($phone)
{
    global $config;
    $url = $config['whatsapp_gateway_url'] . '/auth';
    $result = Http::getData(
        $url,
        [
            "Authorization: Basic " . base64_encode($phone . ":" . $config['whatsapp_gateway_secret'])
        ]
    );
    $json = json_decode($result, true);
    if ($json['status'] == 200) {
        return $json['data']['token'];
    } else {
        if (isset($json['message'])) {
            return "Error: " . $json['message'];
        } else {
            return "Error: " . $result;
        }
    }
}


function whatsappGateway_loginApi($jwt_whatsapp)
{
    global $config;
    if (isset($_GET['pair'])) {
        $url = $config['whatsapp_gateway_url'] . '/login/pair';
    } else {
        $url = $config['whatsapp_gateway_url'] . '/login';
    }
    $result = Http::postData(
        $url,
        [],
        [
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer $jwt_whatsapp"
        ]
    );
    return $result;
}


function whatsappGateway_phoneFormat($phone)
{
    global $config;
    if (!empty($phone) && !empty($config['whatsapp_country_code_phone'])) {
        return preg_replace('/^0/',  $config['whatsapp_country_code_phone'], $phone);
    } else {
        return $phone;
    }
}

<?php

namespace App\Http\Controllers\Wx;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerificationCode;
use App\Services\UserServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 获取参数
        $username = $request->input('username');
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');

        // 验证参数是否为空
        if (empty($username) || empty($password) || empty($mobile) || empty($code)) {
            return ['errno' => 401, 'errmsg' => '参数不对'];
        }
        // 验证用户是否存在
        //（Validator门面方法，Validator::make()，验证数据）
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return ['errno' => 707, 'errmsg' => '手机号格式不正确'];
        }
        $user = (new UserServices())->getByMobile($mobile);
        if (!is_null($user)) {
            return ['errno' => 705, 'errmsg' => '手机号已注册'];
        }

        $user = (new UserServices())->getByUsername($username);
        if (!is_null($user)) {
            return ['errno' => 704, 'errmsg' => '用户名已注册'];
        }
        // 验证验证码是否正确
        // 写入用户表
        $user = new User();
        $user->username = $username;
        $user->password = Hash::make($password);
        $user->mobile = $mobile;
        $user->avatar = "https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64";
        $user->nickname = $username;
        $user->last_login_time = Carbon::now()->toDateTimeString(); //Y-m-d H:i:s
        $user->last_login_ip = $request->getClientIp();
        $user->save();

        // 新用户发券
        // 返回用户信息和token
        return [
            'errno'  => 0,
            'errmsg' => '成功',
            'data'   => [
                'token'    => '',
                'userInfo' => [
                    'nickName'  => $username,
                    'avatarUrl' => $user->avatar
                ]
            ]
        ];
    }

    public function regCaptcha(Request $request)
    {
        // 获取手机号
        $mobile = $request->input('mobile');
        // 验证手机号是否合法
        if (empty($mobile)) {
            return ['errno' => 401, 'errmsg' => '参数不对'];
        }
        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return ['errno' => 707, 'errmsg' => '手机号格式不正确'];
        }
        // 验证手机号是否已注册
        $user = (new UserServices())->getByMobile($mobile);
        if (!is_null($user)) {
            return ['errno' => 705, 'errmsg' => '手机号已注册'];
        }
        // 随机生成6位验证码
        $code = mt_rand(100000, 999999);
        // 防刷验证码，一分钟内只能一次，当天最多10次
        $lock = Cache::add('register_captcha_lock'.$mobile, 1, 60);
        if (!$lock) {
            return ['errno' => 702, 'errmsg' => '验证码未超过1分钟，不能发送'];
        }
        $countKey = 'register_captcha_count'.$mobile;
        if (Cache::has($countKey)) {
            $count = Cache::increment('register_captcha_count'.$mobile, 1);
            if ($count > 10) {
                return ['errno' => 702, 'errmsg' => '验证码当天发送不能超过10次'];
            }
        } else {
            Cache::put($countKey, 1, Carbon::tomorrow()->diffInSeconds(now()));
        }
        // 保存手机号和验证码的关系
        Cache::put('register_captcha'.$mobile, $code, 600);
        // 发送短信
        Notification::route(
            EasySmsChannel::class,
            new PhoneNumber($mobile, 86)
        )->notify(new VerificationCode($code));

        return ['errno' => 0, 'errmsg' => '成功', 'data' => null];
    }
}

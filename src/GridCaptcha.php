<?php

declare(strict_types=1);

namespace yzh52521\GridCaptcha;

use support\Cache;
use support\Request;

class GridCaptcha
{
    /**
     * 验证码图片路径
     * @var string
     */
    protected $captchaImagePath = '';

    /**
     * 验证码缓存key
     * @var string
     */
    protected $cacheKey = 'grid_captcha';

    /**
     * 验证码效验从请求中获取的 Key
     * @var string
     */
    protected $captchaKeyString = 'captcha_key';

    /**
     * 验证码效验从请求中获取的Code key
     * @var string
     */
    protected $captchaKeyCodeString = 'captcha_code';

    /**
     * 验证码key长度
     * @var int
     */
    protected $captchaKeyLength = 64;

    /**
     * 验证码图片后缀
     * @var string
     */
    protected $imageSuffix = 'jpg';

    /**
     * 输出图片质量
     * @var int
     */
    protected $imageQuality = 70;

    /**
     * 生成验证码图片宽
     * @var int
     */
    protected $captchaImageWide = 300;

    /**
     * 生成验证码图片高
     * @var int
     */
    protected $captchaImageHigh = 300;

    /**
     * 验证码有效期(单位秒)
     * @var int
     */
    protected $captchaValidity = 180;

    /**
     * 存储验证码数据效验成功后返回
     * @var array
     */
    protected $captchaData = [];

    /**
     * 生成的随机验证码
     * @var string
     */
    protected $captchaCode = '';

    /**
     * 验证码key
     * @var string
     */
    protected $captchaKey = '';

    /**
     * 存储验证码图片路径
     * @var array
     */
    protected $imageFile = [];


    /**
     * GridCaptcha constructor.
     */
    public function __construct()
    {
        //初始化配置
        $config                 = config('plugin.yzh52521.gridcaptcha.app.gridcaptcha');
        $this->captchaImagePath = $config['image']['path'];
        $this->imageSuffix      = $config['image']['suffix'];
        $this->imageQuality     = $config['image']['quality'];
        $this->captchaImageWide = $config['image']['wide'];
        $this->captchaImageHigh = $config['image']['high'];

        $this->captchaValidity      = $config['captcha']['validity'];
        $this->cacheKey             = $config['captcha']['cache_key'];
        $this->captchaKeyLength     = $config['captcha']['key_length'];
        $this->captchaKeyString     = $config['captcha']['key_string'];
        $this->captchaKeyCodeString = $config['captcha']['code_string'];
    }

    /**
     * 创建验证码
     *
     * @param array $captchaData
     * @return array
     * @throws \Exception
     */
    public function get(array $captchaData = []): array
    {
        return $this->make($captchaData);
    }

    /**
     * 创建验证码初始化
     * @param array $captchaData
     * @return array
     * @throws \Exception
     */
    protected function make(array $captchaData = []): array
    {
        $this->captchaData = $captchaData;
        $this->captchaCode = substr(str_shuffle('012345678'), 0, 4);
        $this->captchaKey  = $this->random($this->captchaKeyLength);
        $this->imageFile   = Cache::remember("$this->cacheKey:path", function () {
            return $this->getImageFile();
        }, 604800);
        Cache::set("$this->cacheKey:data:$this->captchaKey", [
            'captcha_key'  => $this->captchaKey,
            'captcha_code' => $this->captchaCode,
            'captcha_data' => $captchaData,
        ], $this->captchaValidity);
        return $this->generateIntCodeImg();
    }

    /**
     * 效验验证码是否正确
     * @param string $captchaKey
     * @param string $captchaCode
     * @param bool $checkAndDelete
     * @return false|array
     */
    public function check(string $captchaKey, string $captchaCode, bool $checkAndDelete = true)
    {
        //判断是否获取到
        $captcha_data = $checkAndDelete
            ? Cache::get("$this->cacheKey:data:" . $captchaKey, false)
            : Cache::get("$this->cacheKey:data:" . $captchaKey, false);
        if ($captcha_data === false || $captcha_data === null) {
            return false;
        }
        //判断验证码是正确
        if (!empty(
        array_diff(
            str_split($captcha_data['captcha_code']),
            str_split($captchaCode)
        )
        )) {
            return false;
        }
        return $captcha_data['captcha_data'];
    }

    /**
     * 效验验证码是否正确 直接传递 Request 对象方式效验
     * @param Request $request
     * @param bool $checkAndDelete 效验之后是否删除
     * @return false|array
     */
    public function checkRequest(Request $request, bool $checkAndDelete = true)
    {
        $validate = validate(
            [
                $this->captchaKeyString     => "required|string|length:$this->captchaKeyLength",
                $this->captchaKeyCodeString => 'required|integer|between:1,4',
            ]
        );
        if (!$validate->check($request)) {
            return false;
        }
        return $this->check($request[$this->captchaKeyString], $request[$this->captchaKeyCodeString], $checkAndDelete);
    }

    /**
     * 生成九宫格验证码图片
     * @return array
     */
    protected function generateIntCodeImg(): array
    {
        //随机获取正确的验证码
        $correct_str  = array_rand($this->imageFile, 1);
        $correct_path = $this->imageFile[$correct_str];
        $correct_key  = array_rand($correct_path, 4);
        //移除正确的验证码 [方便后面取错误验证码 , 不会重复取到正确的]
        unset($this->imageFile[$correct_str]);

        //循环获取正确的验证码图片
        $correct_img = [];
        foreach ($correct_key as $key) {
            $correct_img[] = $correct_path[$key];
        }

        //循环获取错误的验证码
        $error_key = array_rand($this->imageFile, 5);
        $error_img = [];
        foreach ($error_key as $path_key) {
            $error_path  = $this->imageFile[$path_key];
            $error_img[] = $error_path[array_rand($error_path, 1)];
        }

        //对全部验证码图片打乱排序
        $code_array  = str_split($this->captchaCode);
        $results_img = [];
        for ($i = 0; $i < 9; $i++) {
            $results_img[] = in_array($i, $code_array)
                ? array_shift($correct_img)
                : array_shift($error_img);
        }

        //处理提示文本
        $trans_key = "grid-captcha.$correct_str";
        $hint      = trans($trans_key);
        if ($trans_key == $hint) {
            $hint = $correct_str;
        }

        //组合返回消息
        return [
            'hint'        => $hint,
            'captcha_key' => $this->captchaKey,
            'image'       => $this->combinationCaptchaImg($results_img),
        ];
    }

    /**
     * 组合验证码图片
     * @param array $imgPath
     * @return string
     */
    protected function combinationCaptchaImg(array $imgPath): string
    {
        //初始化参数
        $space_x = $space_y = $start_x = $start_y = $line_x = 0;
        $pic_w   = (int)($this->captchaImageWide / 3);
        $pic_h   = (int)($this->captchaImageHigh / 3);

        //设置背景
        $background = imagecreatetruecolor($this->captchaImageWide, $this->captchaImageHigh);
        $color      = imagecolorallocate($background, 255, 255, 255);
        imagefill($background, 0, 0, $color);
        imageColorTransparent($background, $color);

        foreach ($imgPath as $key => $path) {
            $keys = $key + 1;
            //图片换行
            if ($keys == 4 || $keys == 7) {
                $start_x = $line_x;
                $start_y = $start_y + $pic_h + $space_y;
            }
            //缓存中读取文件
            $gd_resource = imagecreatefromstring(
                Cache::remember(
                    "$this->cacheKey:file:$path",
                    function () use ($path) {
                        return file_get_contents($path);
                    },
                    604800
                )
            );
            imagecopyresized(
                $background,
                $gd_resource,
                $start_x,
                $start_y,
                0,
                0,
                $pic_w,
                $pic_h,
                imagesx($gd_resource),
                imagesy($gd_resource)
            );
            $start_x = $start_x + $pic_w + $space_x;
        }
        ob_start();
        imagejpeg($background, null, $this->imageQuality);
        //释放图片资源
        imagedestroy($background);
        return "data:image/jpeg;base64," . base64_encode(ob_get_clean());
    }

    /**
     * 获取验证码图片
     * @return array
     * @throws \Exception
     */
    protected function getImageFile(): array
    {
        //获取验证码目录下面的图片
        $image_path = glob($this->captchaImagePath . '/*');
        $image_file = [];
        foreach ($image_path as $file) {
            $image_file[pathinfo($file)['basename'] ?? 'null'] = glob("$file/*.$this->imageSuffix");
        }
        unset($image_file['null']);
        if (empty($image_file)) {
            throw new \Exception('找不到验证码图片');
        }
        return $image_file;
    }

    /**
     * 获取验证码
     * @return string
     */
    public function getCaptchaCode(): string
    {
        return $this->captchaCode;
    }

    /**
     * 获取指定长度的随机字母数字组合的字符串
     *
     * @param int $length
     * @param int $type
     * @param string $addChars
     * @return string
     */
    private function random(int $length = 6, int $type = null, string $addChars = ''): string
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书" . $addChars;
                break;
            default:
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($length > 10) {
            $chars = $type == 1 ? str_repeat($chars, $length) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str   = substr($chars, 0, $length);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $str .= mb_substr($chars, (int)floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
            }
        }
        return $str;
    }

}
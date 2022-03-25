# webman-captcha-grid
webman quickly creates a verification code tool similar to Google verification code

webman 快速创建一个类似于 Google 点图验证码的本地验证码扩展
## 介绍

`webman-gridCaptcha` 生成类似于谷歌点图验证码的小扩展，因为现在PHP大部分生成的验证码，对于恶意者来说很容易识别，而这套小扩展很简单但是对于机器人来说需要进行深度的机器学习，恶意者攻击的成本也就增加了，但是这套小扩展不同于谷歌验证码需要机器学习，只需要在本地配置好相应的文件即可。因为生成的验证码图片都是读取文件进行生成，所以建议使用Redis进行缓存，代码默认有使用缓存。

```
ps: 如有不足之处，欢迎大佬提出修改意见。
```
## 预览
![Preview](https://lingshulian.com/s/t/b74e9cf548e1e03c)

## 安装
```shell
 composer require yzh52521/webman-captcha-grid
```

### 配置项说明
```
return [
    //生成验证码图片配置
    'image' => [
        //验证码图片路径
        'path' => env('GRID_CAPTCHA_IMAGE_PATH', public_path().'/storage/gridcaptcha/image'),
        //从验证码图片路径中获取的文件后缀名
        'suffix' => env('GRID_CAPTCHA_IMAGE_SUFFIX', 'jpg'),
        //生成验证码质量
        'quality' => env('GRID_CAPTCHA_IMAGE_QUALITY', 70),
        //生产验证码宽
        'wide' => env('GRID_CAPTCHA_IMAGE_WIDE', 300),
        //生产验证码高
        'high' => env('GRID_CAPTCHA_IMAGE_HIGH', 300),
    ],
    //验证码配置
    'captcha' => [
        //生成的验证码过期时间 单位秒
        'validity' => env('GRID_CAPTCHA_IMAGE_VALIDITY', 180),
        //验证码缓存的key
        'cache_key' => env('GRID_CAPTCHA_IMAGE_CACHE_KEY', 'grid_captcha'),
        //验证码生成的key长度
        'key_length' => env('GRID_CAPTCHA_IMAGE_KEY_LENGTH', 64),
        //自定义效验验证码key字段
        'key_string' => env('GRID_CAPTCHA_IMAGE_KEY_STRING', 'captcha_key'),
        //自定义效验验证码code字段
        'code_string' => env('GRID_CAPTCHA_IMAGE_CODE_STRING', 'captcha_code'),
    ],
];

```
## 使用

- 生成验证码
```php
<?php


namespace app\controller;


class Test
{
   
    /**
     * 生成验证码
     * @return array
     */
    public function captcha()
    {
        $captcha = new \yzh52521\GridCaptcha\GridCaptcha();
        return json($captcha->get([
            'mobile' => '100xxxxx121'
        ]));
    }
}

```

- 生成结果
```json5
{
  "hint": "猴子",//提示文本
  "captcha_key": "Qh8kHYF4C....",//验证码key
  "image": "data:image/jpeg;base64,/9j/...."//base64验证码图片 -- 前端渲染显示
}
```

- 效验验证码

```html
 <!--
     
生成的是一个九宫格图片，前端需要渲染图片，并且生成九个div用于记录用户点击的宫格位置，宫格位置从 0 开始，当点击到四位的时候返回给后端进行效验 ，因为前端技术拙劣我就不放例子了欢迎大佬补充。

大概思路:
-->
<div>
    <!-- img 显示的是返回的验证码图片-->
    <img src="data:image/jpeg;base64...." width="300" height="300" alt="" style="display: block;">
    <div id="0"></div>
    <div id="1"></div>
    <div id="2"></div>
    <div id="3"></div>
    <div id="4"></div>
    <div id="5"></div>
    <div id="6"></div>
    <div id="7"></div>
    <div id="8"></div>
</div>
```
- 效果:
  ![Preview](https://lingshulian.com/s/t/b1fbc5173f8dfd6e)

```php


namespace app\controller;


use support\Request;

class Test 
{

    
    /**
     * 效验
     * @param Request $request
     * @return array|false
     */
    public function check(Request $request)
    {
        $captcha = new \yzh52521\GridCaptcha\GridCaptcha();
        /**
         * 传参效验
         */
        if ($captcha_data = $captcha->check('Qh8kHYF4C....', '1540') === false) {
            return json(['message' => '验证码错误', 'code' => 401]);
        }
        /**
         * 传递 Request 对象效验
         */
        if ($captcha_data = $captcha->checkRequest($request)) {
            return json(['message' => '验证码错误', 'code' => 401]);
        }

        return $captcha_data;
    }
}

    //效验完成正确后 您可以进行业务逻辑处理，比如可以获取到上方设置在验证码中的数据 如：上方设置的是手机号，您这里可以获取验证码中的手机号，当效验成功发送短信验证码等...

```

- 效验成功返回: 返回的是您在生成验证时传递的数据，默认返回空数组
- 效验失败返回: false
```json
{
  "mobile" : "100xxxxx121"
}
```


- 本地化提示
>具体使用  https://www.workerman.net/doc/webman/components/translation.html
 ```
resource/translations/zh_CN/messages.php
```

```php
<?php
//一个图片目录对应一个提示
return [
 'grid-captcha' => [
    'banmaxian' => '斑马线',
    'gongjiaoche' => '公交车',
    'heiban' => '黑板',
    'honglvdeng' => '红绿灯',
    'hongzao' => '红枣',
    'houzi' => '猴子',
    'qianbi' => '铅笔',
    'shutiao' => '薯条',
    'xiaofangshuan' => '消防栓',
    'zhenglong' => '蒸笼',
    ]
];
```

- 新增验证码图片

  例：新增一个类型为 `pingguo` 验证码类型的图片，需要在配置文件中的 `image.path` 目录下创建名为 `pingguo` 的目录并且把相关类型的图片文件存放在 `pingguo` 目录，新增一个类型至少要有四张相关类型的图片，不限制文件名，只要文件后缀名是配置文件中指定的即可如下:
```
─storage
    └─grid-captcha
        └─image
            ├─pingguo
            │       1.jpg
            │       10.jpg
            │       11.jpg
            │       12.jpg
            │       13.jpg
            
```

## License

MIT

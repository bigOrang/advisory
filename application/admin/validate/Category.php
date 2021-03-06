<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

/**
 * 客服验证器
 * @package app\cms\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Category extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|分类名称'     => "require|unique:CategoryModel,name",
        "principal|负责人"       => "require",
    ];

    protected $message  =   [
        'category.require' => '分类名称不能为空',
        'unique.require' => '分类名称不能重复',
        'principal.require'  => '负责人不能为空',
    ];
}

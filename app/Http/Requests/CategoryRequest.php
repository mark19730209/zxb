<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_name'     => 'required|string|max:255', // 报关、退税用中文名
            'hs_code'           => 'required|string|size:10', // 中国海关标准10位商品编码
            'elements_template' => 'required|array', // 要素模板，前端传数组如 ["品牌","材质"], 后端转JSON
            'unit'              => 'required|string|max:20',  // 法定计量单位
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_name.required' => '请输入报关中文名称',
            'hs_code.required' => '请输入海关H.S.编码',
            'hs_code.size'  => '海关H.S.编码必须严格为10位数字',
            'unit.required' => '请输入计量单位',
        ];
    }
}

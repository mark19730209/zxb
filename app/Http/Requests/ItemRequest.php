<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
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
    // public function rules(): array
    // {
    //     return [
    //         'category_id'       => 'required',
    //         'sku'               => 'required|string|max:50|unique:items,sku',
    //         'name_cn'           => 'required|string|max:255', // 报关、退税用中文名
    //         'name_en'           => 'required|string|max:255', // Invoice用英文名
    //         'tax_refund_rate'   => 'required|numeric|between:0,17.00', // 出口退税率（通常为0%, 9%, 13%等）
    //         'purchase_price'    => 'required|numeric|min:0', // 预估含税进货单价
    //     ];
    // }
    public function rules(): array
    {
        // 获取当前路由中的参数 ID
        // 💡 提示：如果你的路由是 /categories/{category}，这里写 'category'
        // 如果是商品单品路由 /items/{item}，这里写 'item'
        $id = $this->route('item')?->id ?? $this->route('item');

        return [
            'category_id'       => 'required',
            'name_cn'           => 'required|string|max:255', // 报关、退税用中文名
            'name_en'           => 'required|string|max:255', // Invoice用英文名
            'tax_refund_rate'   => 'required|numeric|between:0,17.00', // 出口退税率（通常为0%, 9%, 13%等）
            'purchase_price'    => 'required|numeric|min:0', // 预估含税进货单价
            'item_image'        => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // 🎯 限制 5MB 内的高清图
            // 🎯 核心修复：创建时严格唯一，更新时动态排除当前记录 ID
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'sku')->ignore($id),
            ],
        ];
    }
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => '请选择海关大类',
            'sku.required' => '请输入自定义货号',
            'sku.unique'      => '货号(SKU)已存在',
            'name_cn.required' => '请输入报关中文名称',
            'name_en.required' => '请输入报关英文名称',
            'tax_refund_rate.required' => '请输入退税率',
            'tax_refund_rate.between' => '退税率必须在 0.00% 到 17.00% 之间',
            'purchase_price.required' => '请输入国内含税预估价',
        ];
    }
}

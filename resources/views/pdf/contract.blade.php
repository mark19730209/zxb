<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>购销合同 - {{ $contract->purchase_contract_no }}</title>
    <style>
        body {
            /* 🎯 基于 Browsershot 强大的 Chrome 内核，直接使用本地系统字体，几百KB超轻量 */
            font-family: "PingFang SC", "Microsoft YaHei", "SimHei", sans-serif;
            color: #111;
            margin: 15px;
            font-size: 13px;
            max-width: 1050px; /* 完美适配 A4 横向有效显示宽度 */
            margin: 0 auto;
        }
        .header-box {
            width: 100%;
            margin-bottom: 8px;
        }
        .company-name {
            float: left;
            font-weight: bold;
            font-size: 14px;
        }
        .contract-no {
            float: right;
            font-family: "Courier New", Courier, monospace;
            font-size: 13px;
        }
        .clear {
            clear: both;
        }
        .header-title {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 6px;
            margin: 12px 0;
        }
        /* 抬头两栏栅格 */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            line-height: 1.8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: auto; /* 防止跨页时表格严重断裂 */
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #666;
            padding: 0px 0px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background-color: #eaeded; /* 对应你台账原图的淡灰色表头 */
            font-weight: bold;
            font-size: 12px;
        }
        .product-img {
            width: 28px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: #fff;
        }
        .font-mono {
            font-family: "Courier New", Courier, monospace;
        }
        .text-right {
            text-align: right;
            padding-right: 8px;
        }
        .warning-text {
            color: red;
            font-weight: bold;
            font-style: italic;
            margin-top: 8px;
            font-size: 12px;
        }
        /* 🎯 底部签字与绝对定位红色公章层 */
        .footer-sign-box {
            margin-top: 20px;
            position: relative;
            display: table;
            width: 100%;
            line-height: 2.2;
        }
        .sign-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .company-seal {
            position: absolute;
            left: 140px;   /* 🎯 动态对齐微调：让红章完美覆盖在“甲方负责人签章”正上方 */
            top: -15px;
            width: 135px;
            height: 135px;
            opacity: 0.88; /* 开启半透明混色，呈现最真实的物理盖章透视质感 */
            pointer-events: none;
        }
    </style>
</head>
<body>

    <!-- 顶部台账基础条款 -->
    <div class="header-box">
        <div class="company-name">青岛张秀彬国际贸易有限公司</div>
        <div class="contract-no">订 单 号：<strong>{{ $contract->purchase_contract_no }}</strong></div>
        <div class="clear"></div>
    </div>

    <div class="header-title">购销合同</div>

    <!-- 契约双方基本面 -->
    <div class="info-grid">
        <div class="info-column">
            <strong>甲方（需方）：</strong> 青岛张秀彬国际贸易有限公司<br>
            <strong>联系人：</strong> 张真英<br>
            <strong>电 话：</strong> 186-6022-1307
        </div>
        <div class="info-column">
            <strong>乙方（供方）：</strong> {{ $contract->supplier?->company_name ?? '未指定供货工厂' }}<br>
            <strong>联系人：</strong> {{ $contract->supplier?->contact_name ?? '' }}<br>
            <strong>电 话：</strong> {{ $contract->supplier?->contact_phone ?? '' }}
        </div>
    </div>

    <p style="font-size: 11px; color: #444; margin: 5px 0;">
        乙方委托甲方加工生产以下订单，为了明确双方的权利与义务，保障双方的合法权益，经甲﹑乙双方协商决定﹐达成以下协议﹐共同遵守。
    </p>

    <!-- ━━━ 📦 核心商品级联明细多颜色交叉轧算表格 ━━━ -->
    <table>
        <thead>
            <tr>
                <th style="width: 6%;">客户货号</th>
                <th style="width: 24%;">货号</th>
                <th style="width: 8%;">图片</th>
                <th style="width: 10%;">描述</th>
                <th style="width: 10%;">颜色</th>
                <th style="width: 10%;">单价</th>
                <th style="width: 10%;">数量</th>
                <th style="width: 12%;">金额</th>
                <th style="width: 10%;">交货日期</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalQuantity = 0; 
                $totalAmount = 0; 
            @endphp

            @forelse($contract->purchaseContractItems as $index => $item)
                @php
                    // 动态统计总体轧算总额
                    $totalQuantity += ($item->quantity ?? 0);
                    $itemRowAmount = ($item->quantity ?? 0) * ($item->purchase_price ?? 0);
                    $totalAmount += $itemRowAmount;
                @endphp
                <tr>
                    <!-- 客户货号 & SKU 货号 -->
                    <td class="font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $item->item?->sku ?? '-' }}</td>
                    
                    <!-- 🎯 图片相对路径无缝安全穿透转换 -->
                    <td>
                        @if($item->item?->image_path)
                            <img class="product-img" src="{{ public_path('storage/' . $item->item->image_path) }}" alt="SKU图片">
                        @else
                            <div style="font-size: 10px; color: #999;">暂无图片</div>
                        @endif
                    </td>


                    <!-- 海关大类法定品类描述 -->
                    <td style="font-weight: bold;">
                        {{ $item->item?->category?->category_name ?? '未分类货品' }}
                        {{-- @if($item->item?->category?->hs_code)
                            <span style="font-size: 10px; color: #666; block" class="font-mono"><br>({{ $item->item->category->hs_code }})</span>
                        @endif --}}
                    </td>

                    <!-- 规格属性项（如: BUTTER / NAVY） -->
                    <td style="font-weight: bold; color: #444;">{{ $item->color ?? '常规' }}</td>

                    <!-- 单价、数量、金额动态精确渲染 -->
                    <td class="font-mono text-right">¥{{ number_format($item->purchase_price ?? 0, 2) }}</td>
                    <td class="font-mono text-right">
                        {{ number_format($item->quantity ?? 0) }}
                        <span style="font-size: 10px; color: #666;">{{ $item->item?->category?->unit ?? '件' }}</span>
                    </td>
                    <td class="font-mono text-right" style="font-weight: bold;">
                        ¥{{ number_format($itemRowAmount, 2) }}
                    </td>

                    <!-- 交货结账账期 -->
                    <td class="font-mono">{{ $contract->delivery_date ?? $contract->signing_date }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="color: #999; padding: 20px;">该采购合同暂无具体货品款式明细。</td>
                </tr>
            @endforelse

            <!-- ━━━ 📐 轧算总计尾行 ━━━ -->
            <tr style="background-color: #fafbfc; font-weight: bold;">
                <td colspan="6" style="text-align: left; padding-left: 10px; font-size: 13px;">合计</td>
                <td class="font-mono text-right" style="color: #111;">{{ number_format($totalQuantity) }}</td>
                <td class="font-mono text-right" style="color: #1a5276; font-size: 14px;">¥{{ number_format($totalAmount, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="warning-text">***每个颜色各加10个额外损耗备品***</div>

    <!-- ━━━ 🖋️ 底部确权签章与物理盖章区 ━━━ -->
    <div class="footer-sign-box">
        <div class="sign-column">
            <strong>甲方：</strong> 青岛张秀彬国际贸易有限公司<br>
            <strong>地址：</strong> 山东省青岛市胶州市上海路141号7#1-2602<br>
            <strong>负责人签章：</strong>
            
            <!-- 🎯 公章绝对定位完美堆叠覆盖（请确保该相对路径下有对应的png透明公章文件） -->
            @if(file_exists(public_path('images/seal.png')))
                <img class="company-seal" src="{{ public_path('images/seal.png') }}" alt="公司公章">
            @endif
        </div>
        
        <div class="sign-column">
            <strong>乙方：</strong> {{ $contract->supplier?->company_name ?? '未指定供货工厂' }}<br>
            <strong>地址：</strong> {{ $contract->supplier?->company_address ?? '浙江省桐庐县横村方埠工业园区' }}<br>
            <strong>负责人签章：</strong>
        </div>
    </div>

</body>
</html>

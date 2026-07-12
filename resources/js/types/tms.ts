// 1. 海关法定品类大类契约
export interface Category {
  id: number
  hs_code: string        // 10位 H.S. 编码
  category_name: string  // 品类名，如：针织帽子
  unit: string           // 法定第一计量单位 (千克 / 件)
  elements_template: string[] // 依法必填的要素串模板标签
}

// 2. 具体款式 SKU 商品契约
export interface Item {
  id: number
  category_id: number
  sku: string            // 行级款式货号
  name_cn: string        // 款式中文描述
  name_en: string        // 款式英文描述
  tax_refund_rate: number
  purchase_price: number
  image_path: string | null // 🎯 核心新加：存放服务器返回的物理相对路径
  is_actived: boolean
  // 🎯 级联嵌套保护
  category?: Category
}

// export interface Item {
//   id: number
//   sku: string
//   name_cn: string
//   name_en: string
//   hs_code: string
//   elements_template: string[]
//   unit: string
//   tax_refund_rate: number
//   purchase_price: number
//   created_at: string
//   updated_at: string
// }

export interface CategoryFilters {
  search?: string
}

export interface ItemFilters {
  search?: string
}

export interface Pagination<T> {
  data: T[]
  current_page: number
  first_page_url: string | null
  from: number | null
  last_page: number
  last_page_url: string | null
  links: {
    url: string | null
    label: string
    active: boolean
  }[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export interface CategoryPageProps {
  categories: Pagination<Category>
  filters: {
    search?: string
  }
}
export interface ItemPageProps {
  items: Pagination<Item>
  filters: {
    search?: string
  }
}

export interface Supplier {
  id: number
  company_name: string     // 供应商公司全称（开票抬头，如：桐庐华艺针织有限公司）
  tax_id: string           // 统一社会信用代码（18位税号，如：91330122720040617G）
  company_address: string  // 法定注册地址（发票地址）
  company_phone: string    // 企业电话
  bank_name: string        // 开户银行全称（如：桐庐农村商业银行横村支行）
  bank_account: string     // 对公银行账号（如：201000003721565）
  bank_code: string  // 大额支付银行行号 / 联行号（12位，如：402331006063）
  contact_person: string 
  created_at: string
  updated_at: string
}

// 供货商列表页专属过滤与 Props
export interface SupplierFilters {
  search?: string
}

export interface SupplierPageProps {
  suppliers: Pagination<Supplier>
  filters: SupplierFilters
}


export interface Importer {
  id: number
  company_name_en: string     // 境外买方英文全称（发票抬头）
  company_address_en: string  // 境外法定注册及清关送货英文地址
  country_code: string        // 贸易国别代码（3位大写，如：USA, KOR, DEU）
  contact_email: string 
  tax_no: string        // 境外客户清关税号（如欧洲 EORI 号）
  created_at: string
  updated_at: string
}

// 境外进口商列表页专属过滤与 Props
export interface ImporterFilters {
  search?: string
}

export interface ImporterPageProps {
  importers: Pagination<Importer>
  filters: ImporterFilters
}


export interface Exporter {
  id: number
  company_name_cn: string     // 境外买方英文全称（发票抬头）
  company_name_en: string     // 境外买方英文全称（发票抬头）
  company_address: string  // 境外法定注册及清关送货英文地址
  // country_code: string        // 贸易国别代码（3位大写，如：USA, KOR, DEU）
  contact_tel: string 
  tax_id: string        // 境外客户清关税号（如欧洲 EORI 号）
  customs_code: string    // 海关注册号（如欧洲 EORI 号）
  bank_name: string        // 开户银行全称（如：桐庐农村商业银行横村支行）
  bank_account: string     // 对公银行账号（如：201000003721565）
  swift_code: string // SWIFT 国际银行代码（8或11位，如：ICBKCNBJSHI）
  bank_address: string  // 境外法定注册及清关送货英文地址
  // bank_code: string  // 大额支付银行行号 / 联行号（12位，如：402331006063）
  // iban_code: string   // IBAN 国际银行账号（如：DE89370400440532013000）
  created_at: string
  updated_at: string
}

// 境外出口商列表页专属过滤与 Props
export interface ExporterFilters {
  search?: string
}

export interface ExporterPageProps {
  exporters: Pagination<Exporter>
  filters: ExporterFilters
}


export interface ContractItem {
  id: number
  contract_id: number
  item_id: number
  supplier_id: number // 关联的国内生产及开票工厂 ID
  quantity: number    // 签约外销数量
  unit_price: number  // 外销单价 (外币)
  total_amount: number // 外销总价 (外币)
  
  // 物流装箱核心参数（直接决定 Packing List 渲染）
  packages: number
  package_type: string // 包装种类 (如: Cartons / CTNS / PKGS)
  net_weight: number   // 总净重 (KG)
  gross_weight: number // 总毛重 (KG)
  volume: number       // 总体积 (CBM)
  
  // 申报核心存根
  declared_elements: string | null // 拼装好的海关“|”分隔要素长串

  // 🎯 深度级联：后端 show() 路由中预加载的基础商品档案和反序列化要素数组
  item?: Item
  supplier?: Supplier // 👈 笔直插入这一行！
  element_values_array?: string[] // 前端动态解开的要素填空值数组
}

export interface FinancialTracker {
  id: number
  contract_id: number
  purchase_total_amount: number   // 整个合同国内上游多商户采购应开含税发票总面额
  received_invoice_amount: number  // 财务人员目前实际收到并录入通过的专票累计面额
  estimated_refund: number         // 依据商品退税率公式智能推演得出的“预计可退税额”大盘底账
  actual_refund_received: number   // 国家税务局国库实际打款到账的累计退税金额
  refund_apply_date: string | null
  refund_receive_date: string | null
  created_at: string
  updated_at: string
}


export interface Contract {
  id: number
  contract_no: string  // 出口合同号 (如: CT20260625)
  exporter_id: number  // 本国发货出口商 ID
  importer_id: number  // 境外进口商 ID
  contract_date: string
  currency: 'USD' | 'EUR' | 'GBP' | 'CNY' // 结汇币种硬约束
  incoterms: 'FOB' | 'CIF' | 'CFR' | 'EXW' // 国际通用贸易术语
  payment_terms: string // 收汇付款条款
  transport_mode: number        // 默认 2 | 水路运输
  port_of_loading: string     // 默认上海港
  port_of_destination: string     // 目的港等待进口商选择级联触发
  // 🎯 核心业务生命周期状态机联合类型约束
  status: 'draft' | 'active' | 'shipped' | 'completed' | 'cancelled'
  invoice_status: 'none' | 'partial' | 'fully_issued' // 工厂发票回收状态
  refund_status: 'none' | 'processing' | 'received'  // 国家退税进度状态
  
  created_at: string
  updated_at: string

  // 🎯 深度模型关联嵌套（HasMany / HasOne 映射）
  contract_items?: ContractItem[]
  financial_tracker?: FinancialTracker | null
}


export interface ContractFilters {
  search?: string
}

export interface ContractPageProps {
  contracts: Pagination<Contract>
  filters: ContractFilters
}

export interface PurchaseInvoiceWithSupplier {
  id: number
  contract_id: number
  supplier_id: number
  invoice_code: string
  invoice_no: string
  file_path: string | null // 🎯 存放服务器返回的物理相对路径
  issue_date: string
  tax_exclusive_amount: number
  tax_amount: number
  total_amount: number
  status: 'verified' | 'audit_failed'
  created_at: string
  updated_at: string
  // 🎯 核心级联：包含反向关联的国内工厂实体
  supplier?: Supplier 
}

export interface AnalyticsMetrics {
  total_export_usd: number
  total_export_rmb: number
  total_purchase_amount: number
  total_received_invoice: number
  total_estimated_refund: number
  total_actual_refund: number
  total_gross_profit: number
  invoice_recovery_rate: number // 进货发票收交率 (%)
  refund_recovery_rate: number  // 出口退税到账率 (%)
}

export interface ContractPerformance {
  id: number
  contract_no: string
  status: 'draft' | 'active' | 'shipped' | 'completed' | 'cancelled'
  export_amount_usd: number
  export_amount_rmb: number
  purchase_total_amount: number
  invoice_status: 'none' | 'partial' | 'fully_issued'
  estimated_refund: number
  actual_refund_received: number
  net_profit: number
}

export interface AnalyticsPageProps {
  metrics: AnalyticsMetrics
  performanceList: ContractPerformance[]
}

export interface InvoiceAllocationPayload {
  contract_id: number
  amount: number
}

export interface MultiInvoiceFormPayload {
  supplier_id: number | string
  invoice_code: string
  invoice_no: string
  issue_date: string
  total_amount: number
  tax_rate: string
  allocations: InvoiceAllocationPayload[]
}

export interface InvoiceAllocationWithContract {
  id: number
  purchase_invoice_id: number
  contract_id: number
  allocated_amount: number
  tax_exclusive_amount: number
  tax_amount: number
  contract?: {
    id: number
    contract_no: string
  }
}

export interface FullPurchaseInvoice {
  id: number
  supplier_id: number
  invoice_no: string
  file_path: string | null // 🎯 存放服务器返回的物理相对路径
  issue_date: string
  tax_exclusive_amount: number
  tax_amount: number
  total_amount: number
  status: 'verified' | 'audit_failed'
  supplier?: {
    id: number
    company_name: string
    tax_id: string
  }
  // 🎯 级联嵌套：包含这张大发票拆分拆摊出去的所有合同明细
  allocations?: InvoiceAllocationWithContract[] 
}

export interface PurchaseInvoicePageProps {
  invoices: Pagination<FullPurchaseInvoice>
  suppliers: Array<{ id: number, company_name: string, tax_id: string }>
  activeContracts: Array<{ id: number, contract_no: string, invoice_status: string }>
  filters: { search?: string }
}

export interface ContractItemsPageProps {
  contract: Contract
  contractItems: ContractItem[]
  availableItems: Item[]
  availableSuppliers: Supplier[]
}
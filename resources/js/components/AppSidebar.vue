<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    ContactRound,
    Building2,
    FormInput,
    LayoutGrid,
    Navigation,
    Database,
    Zap,
    Layers,
    Radio,
    AlertTriangle,
    Wifi,
    Save,
    Package,       // Items: 货品、商品、库存
    Handshake,     // Suppliers: 供应商、商务合作
    FileDown,      // Importers: 进口商、单证引入
    FileText,      // Contracts: 合同、协议文档
    ChartPie       // Analytics: 数据分析、报表
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavSection from '@/components/NavSection.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type NavSection as NavSectionType } from '@/types';
import AppLogo from './AppLogo.vue';

const crmNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Organizations',
        href: '/organizations',
        icon: Building2,
    },
];
const tmsNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];
const ERPTMS: NavSectionType = {
    label: 'ERP/TMS',
    groups: [
        {
            title: '出口合同',
            icon: FileText,
            items: [
                { title: '出口合同列表', href: '/contracts'},
                { title: '起草出口合同', href: '/contracts/create'},
                // { title: 'MultiInvoice', href:'/financials/multi-invoice'},
            ]
        },
        {
            title: '发票管理',
            icon: FileText,
            items: [
                { title: '发票管理', href: '/invoices'},
                // { title: 'Add Invoices', href: '/invoices/create'},
                // { title: 'MultiInvoice', href:'/financials/multi-invoice'},
            ]
        },
        {
            title: '海关HS大类',
            icon: Package,
            items: [
                { title: '海关 H.S. 品类库', href: '/categories' },
                { title: '新增海关大类', href: '/categories/create'}
            ]
        },
        {
            title: '海关商品',
            icon: Package,
            items: [
                { title: '大货款式 SKU 库', href: '/items'},
                { title: '款式建档 SKU', href: '/items/create'},
            ]
        },
        {
            title: '国内供货工厂',
            icon: Handshake,
            items: [
                { title: '国内供货工厂列表', href: '/suppliers'},
                { title: '登记国内供货工厂', href: '/suppliers/create'},
                { title: '国内供货合同', href: '/purchase-contracts'},
            ]
        },
        {
            title: '国外买方',
            icon: FileDown,
            items: [
                { title: '国外清关买方', href: '/importers'},
                { title: '录入国外买方', href: '/importers/create'},
            ]
        },
        {
            title: '国内出口商',
            icon: FileDown,
            items: [
                { title: '国内出口商列表', href: '/exporters'},
                { title: '录入国内出口商', href: '/exporters/create'},
            ]
        },
        {
            title: '全盘透视',
            icon: ChartPie,
            items: [
                { title: '全盘透视', href: '/analytics'},
            ]
        }
    ]
}
const crmSection: NavSectionType = {
    label: 'CRM demo',
    groups: [
        {
            title: 'Contacts',
            icon: ContactRound,
            items: [
                { title: 'All Contacts', href: '/contacts' },
                { title: 'Create Contact', href: '/contacts/create' },
            ],
        },
    ],
};

const featuresSection: NavSectionType = {
    label: 'Kitchen Sink',
    groups: [
        {
            title: 'Forms',
            icon: FormInput,
            items: [
                { title: 'useForm', href: '/features/forms/use-form' },
                {
                    title: 'Form Component',
                    href: '/features/forms/form-component',
                },
                { title: 'File Uploads', href: '/features/forms/file-uploads' },
                { title: 'Validation', href: '/features/forms/validation' },
                { title: 'Precognition', href: '/features/forms/precognition' },
                {
                    title: 'Optimistic Updates',
                    href: '/features/forms/optimistic-updates',
                    badge: 'v3',
                },
                {
                    title: 'useFormContext',
                    href: '/features/forms/use-form-context',
                },
                { title: 'Dotted Keys', href: '/features/forms/dotted-keys' },
                { title: 'Wayfinder', href: '/features/forms/wayfinder' },
            ],
        },
        {
            title: 'Navigation',
            icon: Navigation,
            items: [
                {
                    title: 'Links & Methods',
                    href: '/features/navigation/links',
                },
                {
                    title: 'Preserve State',
                    href: '/features/navigation/preserve-state',
                },
                {
                    title: 'Preserve Scroll',
                    href: '/features/navigation/preserve-scroll',
                },
                {
                    title: 'View Transitions',
                    href: '/features/navigation/view-transitions',
                },
                {
                    title: 'History Management',
                    href: '/features/navigation/history-management',
                },
                {
                    title: 'Async Requests',
                    href: '/features/navigation/async-requests',
                },
                {
                    title: 'Client-Side Visits',
                    href: '/features/navigation/manual-visits',
                },
                {
                    title: 'Redirects',
                    href: '/features/navigation/redirects',
                },
                {
                    title: 'Scroll Management',
                    href: '/features/navigation/scroll-management',
                },
                {
                    title: 'Instant Visits',
                    href: '/features/navigation/instant-visits',
                    badge: 'v3',
                },
                {
                    title: 'URL Fragments',
                    href: '/features/navigation/url-fragments',
                    badge: 'v3',
                },
            ],
        },
        {
            title: 'Data Loading',
            icon: Database,
            items: [
                {
                    title: 'Deferred Props',
                    href: '/features/data-loading/deferred-props',
                },
                {
                    title: 'Partial Reloads',
                    href: '/features/data-loading/partial-reloads',
                },
                {
                    title: 'Infinite Scroll',
                    href: '/features/data-loading/infinite-scroll',
                },
                {
                    title: 'When Visible',
                    href: '/features/data-loading/when-visible',
                },
                { title: 'Polling', href: '/features/data-loading/polling' },
                {
                    title: 'Prop Merging',
                    href: '/features/data-loading/prop-merging',
                },
                {
                    title: 'Once Props',
                    href: '/features/data-loading/once-props',
                },
                {
                    title: 'Optional Props',
                    href: '/features/data-loading/optional-props',
                },
            ],
        },
        {
            title: 'Prefetching',
            icon: Zap,
            items: [
                {
                    title: 'Link Prefetch',
                    href: '/features/prefetching/link-prefetch',
                },
                {
                    title: 'Stale While Revalidate',
                    href: '/features/prefetching/stale-while-revalidate',
                },
                {
                    title: 'Manual Prefetch',
                    href: '/features/prefetching/manual-prefetch',
                },
                {
                    title: 'Cache Management',
                    href: '/features/prefetching/cache-management',
                },
            ],
        },
        {
            title: 'State Management',
            icon: Save,
            items: [
                { title: 'Remember', href: '/features/state/remember' },
                { title: 'Flash Data', href: '/features/state/flash-data' },
                { title: 'Shared Props', href: '/features/state/shared-props' },
            ],
        },
        {
            title: 'Layouts & Head',
            icon: Layers,
            items: [
                {
                    title: 'Persistent Layouts',
                    href: '/features/layouts/persistent-layouts',
                },
                {
                    title: 'Nested Layouts',
                    href: '/features/layouts/nested-layouts',
                },
                { title: 'Head Component', href: '/features/layouts/head' },
                {
                    title: 'Layout Props',
                    href: '/features/layouts/layout-props',
                    badge: 'v3',
                },
            ],
        },
        {
            title: 'Events & Lifecycle',
            icon: Radio,
            items: [
                {
                    title: 'Global Events',
                    href: '/features/events/global-events',
                },
                {
                    title: 'Visit Callbacks',
                    href: '/features/events/visit-callbacks',
                },
                { title: 'Progress Bar', href: '/features/events/progress' },
            ],
        },
        {
            title: 'Error Handling',
            icon: AlertTriangle,
            items: [
                {
                    title: 'HTTP Exceptions',
                    href: '/features/errors/http-exceptions',
                    badge: 'v3',
                },
                {
                    title: 'Network Errors',
                    href: '/features/errors/network-errors',
                    badge: 'v3',
                },
            ],
        },
        {
            title: 'HTTP',
            icon: Wifi,
            items: [
                {
                    title: 'useHttp',
                    href: '/features/http/use-http',
                    badge: 'v3',
                },
            ],
        },
    ],
};

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: 'https://inertiajs.com/docs/v3/getting-started',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavSection :section="ERPTMS" :items="tmsNavItems" />
            <NavSection :section="crmSection" :items="crmNavItems" />
            <NavSection :section="featuresSection" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

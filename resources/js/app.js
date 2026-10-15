/*
 * حزمة الواجهة: كانت أربع مكتبات تُحمَّل من CDN وقت فتح الصفحة، فينقطع
 * الإنترنت فتظهر الشاشة بلا تنسيق ولا تفتح نوافذها ولا يعمل مُنتقي التاريخ.
 * صارت تُبنى محلياً وتُخدَم من الجهاز نفسه.
 *
 * ترتيب التحميل محفوظ كما كان: flatpickr وتعريبه قبل Alpine، لأن مكوّنات
 * Alpine تستدعيه عند تهيئتها.
 */
import './bootstrap';

import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';

// الصفحات تستدعي هذه الثلاثة من وسوم <script> مباشرةً في الصفحة، فتبقى
// على النطاق العام كما كانت أيام الـCDN
window.flatpickr = flatpickr;
window.Chart = Chart;
window.Alpine = Alpine;

flatpickr.localize(Arabic);

Alpine.start();

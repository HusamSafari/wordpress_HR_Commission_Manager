# Branch HR & Commission Manager (SWVT-HR)
> A comprehensive WordPress plugin for managing branch employees, hierarchical organizational structures, daily attendance grids, and automated sales commissions.
> إضافة ووردبريس متكاملة لإدارة موظفي الفروع، الهياكل الإدارية الهرمية، شبكات الغياب اليومية، وحساب عمولات المبيعات التلقائية.

---

## 🇺🇸 English Description

`SWVT-HR` is a robust HR and ERP-style management plugin designed for businesses with multiple branches. It streamlines employee records, tracks organizational hierarchies, logs granular attendance details, and calculates complex sales commissions dynamically.

### Key Features
*   **Hierarchical Structure Management**: Build interactive visual trees for Company Departments and Job Titles with parent-child relationships and inheritance loop safety.
*   **Dynamic Attendance Calendar Grid**: Record day-by-day staff absences with variable deduction weights (Quarter Day, Half Day, Full Day, Two Days) and automatically calculate salary deductions.
*   **Annual Target Increase & Distribution**: Apply percentage target increases to specific branches for any target year, distributing the increase month-by-month.
*   **Wages & Payroll Processing**: Automatically calculate base salaries, attendance deductions, role-based commissions, and compile complete monthly ledgers.
*   **RTL & Theme Compatibility**: Fully compatible with LTR and RTL Arabic dashboards, supporting custom light and dark modes.

### Tech Stack
*   **Backend**: PHP, WordPress Database API (`$wpdb`, Custom Database Tables)
*   **Frontend**: JavaScript (jQuery), CSS Custom Properties (Variables), WordPress Dashicons
*   **RTL Styling**: Custom styling stylesheets designed specifically for Arabic administration panels.

---

## 🇪🇬 الوصف باللغة العربية

`SWVT-HR` هي إضافة ووردبريس قوية مخصصة لإدارة الموارد البشرية (HR) والعمليات المالية للشركات ذات الفروع المتعددة. تسهل الإضافة إدارة سجلات الموظفين، تتبع الهياكل الإدارية، تسجيل تفاصيل الغياب اليومية، واحتساب عمولات المبيعات بديناميكية متكاملة.

### الميزات الرئيسية
*   **إدارة الهياكل التنظيمية الهرمية**: بناء شجرة هيكلية تفاعلية لإدارات الشركة والمسميات الوظيفية تتيح العلاقات الأبوية مع نظام حماية ذكي يمنع التداخل الحلقي.
*   **لوحة تسجيل الغياب اليومية (Calendar Grid)**: نموذج إدخال غيابات يومي بوزن خصم مرن (ربع يوم، نصف يوم، يوم كامل، يومين) مع احتساب وتعديل فوري لقيم الخصومات المالية من الراتب الأساسي.
*   **الزيادة السنوية للتارجت وتوزيعها شهرياً**: إمكانية تطبيق نسبة زيادة سنوية على تارجت الفروع (مثل 5%) لتتوزع تلقائياً كزيادة شهرية تراكمية على مدار أشهر السنة الـ 12.
*   **مسير الرواتب والعمليات المالية**: احتساب تلقائي للرواتب الأساسية، خصومات الغياب، العمولات المستحقة بناءً على المبيعات المحققة والنسب التراكمية، وإصدار كشوفات شهرية متكاملة.
*   **دعم كامل للاتجاهين (RTL/LTR)**: متوافقة تماماً مع لوحات التحكم العربية والإنجليزية، وتدعم الوضع الداكن والفاتح المخصصين.

### التقنيات المستخدمة
*   **الخلفية**: لغة PHP، قواعد بيانات ووردبريس (Custom SQL Tables & `$wpdb` API).
*   **الواجهة**: لغة JavaScript (jQuery)، تنسيقات CSS (Custom Variables)، وأيقونات Dashicons.
*   **دعم RTL**: تصميم وتنسيق مخصص وملائم للوحات التحكم المكتوبة باللغة العربية.

---

## 📸 Screenshots / صور توضيحية

### HR Dashboard / لوحة التحكم الرئيسية
![HR Dashboard](https://raw.githubusercontent.com/username/repository/main/screenshots/dashboard.png)

### Attendance Calendar Grid / جدول تسجيل الغياب اليومي
![Attendance Grid](https://raw.githubusercontent.com/username/repository/main/screenshots/attendance.png)

### Visual Settings Tree Editor / محرر الهياكل التنظيمية الهرمية
![Settings Tree](https://raw.githubusercontent.com/username/repository/main/screenshots/settings.png)

---

## 🛠 Installation & Setup / التثبيت والإعداد

1.  Download the repository as a ZIP file.
    قم بتحميل المستودع كملف ZIP.
2.  Upload it to your WordPress site under **Plugins > Add New > Upload Plugin**.
    قم برفعه إلى موقع ووردبريس الخاص بك من خلال **الإضافات > أضف جديد > رفع إضافة**.
3.  Activate the plugin. Custom database tables will be created automatically.
    قم بتفعيل الإضافة. سيتم إنشاء جداول قاعدة البيانات المخصصة تلقائياً.
4.  Navigate to **HR Dashboard** to start configuring branches and employees.
    توجه إلى **HR Dashboard** للبدء في تهيئة الفروع وإضافة الموظفين.

<?php
/**
 * Salary Appraisal & Cost of Living Case Study View.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83M22 12A10 10 0 0 1 12 22v-4M22 12h-4"></path></svg>';
$page_title = __( 'Salary Appraisal & Cost of Living Analysis', 'swvt-hr' );
?>

<style>
    .swvt-hr-appraisal-wrap {
        direction: rtl;
        text-align: right;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .swvt-hr-appraisal-header {
        margin-bottom: 25px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border-right: 4px solid #2271b1;
    }
    .swvt-hr-appraisal-header h1 {
        font-size: 22px;
        margin: 0 0 8px 0;
        color: #1d2327;
        font-weight: 700;
    }
    .swvt-hr-appraisal-header p {
        font-size: 13.5px;
        color: #646970;
        margin: 0;
        line-height: 1.6;
    }
    .swvt-hr-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid #dcdcde;
        padding-bottom: 1px;
    }
    .swvt-hr-tab-btn {
        background: #f6f7f7;
        border: 1px solid #dcdcde;
        border-bottom: none;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        color: #50575e;
        border-radius: 6px 6px 0 0;
        transition: all 0.15s ease-in-out;
    }
    .swvt-hr-tab-btn:hover {
        background: #f0f0f1;
        color: #1d2327;
    }
    .swvt-hr-tab-btn.is-active {
        background: #fff;
        border-color: #dcdcde;
        border-bottom: 2px solid #2271b1;
        color: #2271b1;
        box-shadow: 0 -2px 4px rgba(0,0,0,0.02);
    }
    .swvt-hr-tab-content {
        display: none;
        background: #fff;
        padding: 12px 16px;
        border-radius: 0 0 8px 8px;
        border: 1px solid #dcdcde;
        border-top: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .swvt-hr-tab-content.is-active {
        display: block;
    }
    .swvt-hr-grid-cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    @media (max-width: 900px) {
        .swvt-hr-grid-cols {
            grid-template-columns: 1fr;
        }
    }
    .swvt-hr-section-title {
        font-size: 13.5px;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 8px;
        padding-bottom: 4px;
        border-bottom: 2px solid #f0f0f1;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .swvt-hr-expense-title {
        color: #b32d2e;
        border-bottom-color: #fbe9ea;
    }
    .swvt-hr-income-title {
        color: #137333;
        border-bottom-color: #e6f4ea;
    }
    .swvt-hr-appraisal-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .swvt-hr-appraisal-table th {
        background: #f6f7f7;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 11px;
        text-align: right;
        border-bottom: 2px solid #dcdcde;
        color: #3c434a;
    }
    .swvt-hr-appraisal-table td {
        padding: 4px 10px;
        font-size: 12px;
        border-bottom: 1px solid #f0f1f2;
        color: #2c3338;
        vertical-align: middle;
    }
    .swvt-hr-appraisal-table tr:hover td {
        background-color: #fdfdfe;
    }
    .swvt-hr-summary-box {
        margin-top: 15px;
        padding: 10px 15px;
        border-radius: 8px;
        background: #f8f9fa;
        border: 1px solid #dcdcde;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
        text-align: center;
    }
    @media (max-width: 600px) {
        .swvt-hr-summary-box {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
    .swvt-hr-summary-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .swvt-hr-summary-label {
        font-size: 11px;
        color: #646970;
        font-weight: 600;
    }
    .swvt-hr-summary-value {
        font-size: 16px;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .swvt-hr-note-box {
        margin-top: 12px;
        padding: 8px 12px;
        border-radius: 6px;
        background: #fff8e1;
        border-right: 4px solid #ffb300;
        font-size: 11.5px;
        color: #5d4037;
        line-height: 1.5;
    }
    
    /* Interactive input styles */
    .swvt-hr-appraisal-input {
        border: 1px solid transparent;
        background: transparent;
        text-align: left;
        font-weight: 700;
        width: 80px;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 12px;
        color: #1d2327;
        font-family: inherit;
        transition: all 0.15s ease-in-out;
        box-sizing: border-box;
    }
    .swvt-hr-appraisal-input:hover {
        border-color: #ccd0d4;
        background: #f6f7f7;
    }
    .swvt-hr-appraisal-input:focus {
        border-color: #2271b1;
        background: #fff;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    /* Hide input spin buttons */
    .swvt-hr-appraisal-input::-webkit-outer-spin-button,
    .swvt-hr-appraisal-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .swvt-hr-appraisal-input[type=number] {
        -moz-appearance: textfield;
    }

    /* Actions Bar */
    .swvt-hr-appraisal-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: flex-start;
        align-items: center;
    }

    /* Notification toast */
    .swvt-hr-toast {
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: #137333;
        color: #fff;
        padding: 12px 24px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-size: 13px;
        font-weight: 600;
        z-index: 99999;
        display: none;
    }
</style>

<div class="wrap swvt-hr-appraisal-wrap">
    <!-- Header -->
    <div class="swvt-hr-appraisal-header">
        <h1>دراسة حالة مصروفات ودخول الموظفين في مصر (Salary Appraisal)</h1>
        <p>
            تستعرض هذه الصفحة تحليلاً تفصيلياً واقتصادياً لنماذج معيشية لمختلف فئات الموظفين في مصر (أعزب، متزوج بدون أطفال، ومتزوج ولديه أطفال). 
            توضح الدراسة العلاقة بين <strong>الراتب الأساسي المرتبط بالخبرة والوظيفة</strong> والمزايا والبدلات الأخرى، مقارنة بـ <strong>تكلفة المعيشة والالتزامات الشهرية الأساسية</strong> بالجنيه المصري (EGP).
            <br>
            <span style="font-weight: 600; color: #2271b1;">💡 يمكنك تعديل أي قيمة تقديرية في الجدول مباشرة بالضغط عليها، ثم الضغط على زر "حفظ التعديلات" لحفظها وحساب الإجماليات فوراً.</span>
        </p>
    </div>

    <!-- Actions Bar -->
    <div class="swvt-hr-appraisal-actions">
        <button type="button" id="swvt-hr-save-appraisal" class="swvt-hr-btn swvt-hr-btn-primary" style="padding: 7px 16px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            حفظ التعديلات الحالية
        </button>
        <button type="button" id="swvt-hr-reset-appraisal" class="swvt-hr-btn swvt-hr-btn-secondary" style="padding: 7px 14px;">
            إعادة تعيين للقيم الافتراضية
        </button>
    </div>

    <!-- Tabs Navigation -->
    <div class="swvt-hr-tabs">
        <button type="button" class="swvt-hr-tab-btn is-active" data-tab="tab-single">
            🟢 الموظف الأعزب (خبرة مبتدئة)
        </button>
        <button type="button" class="swvt-hr-tab-btn" data-tab="tab-married-no-kids">
            🔵 متزوج بدون أطفال (خبرة متوسطة)
        </button>
        <button type="button" class="swvt-hr-tab-btn" data-tab="tab-married-kids">
            🟣 متزوج ومعه أطفال (خبرة متقدمة)
        </button>
    </div>

    <!-- TAB 1: SINGLE EMPLOYEE -->
    <div class="swvt-hr-tab-content is-active" id="tab-single">
        <div style="background: #fafbfe; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #eaf2fb;">
            <div style="font-weight: 700; color: #2271b1; font-size: 14.5px;">النموذج الأول: الموظف الأعزب</div>
            <div style="font-size: 12.5px; color: #50575e; margin-top: 5px; line-height: 1.5;">
                <strong>الوظيفة المفترضة:</strong> مصمم جرافيك مبتدئ (Junior Graphic Designer) · 
                <strong>الخبرة:</strong> 1-2 سنة · 
                <strong>الراتب الأساسي الحالي:</strong> <span class="basic-salary-display" style="font-weight: bold; color: #2c3338;">8,500</span> ج.م (يعكس قيمة الخبرة والمؤهل للوظيفة).
            </div>
        </div>

        <div class="swvt-hr-grid-cols">
            <!-- Expenses Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-expense-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الأول: المصروفات الشهرية الأساسية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند الأساسي للمصروفات</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>السكن أو المساهمة في السكن (إيجار أو مساهمة مع الأسرة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_rent" value="3000" />
                            </td>
                        </tr>
                        <tr>
                            <td>الأكل والشرب (طعام منزلي وبقالة شهرية)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_food" value="4000" />
                            </td>
                        </tr>
                        <tr>
                            <td>المواصلات (وسائل النقل اليومية أو الوقود)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_transport" value="1500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الكهرباء والمياه والغاز (مشاركة في الاستهلاك)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_utilities" value="500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الموبايل والإنترنت (باقات الاتصالات والشبكات)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_mobile" value="400" />
                            </td>
                        </tr>
                        <tr>
                            <td>العلاج والطوارئ (الأدوية والحالات العارضة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_medicine" value="500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الملابس والاحتياجات الشخصية والضروريات</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_clothes" value="1000" />
                            </td>
                        </tr>
                        <tr style="opacity: 0.6;">
                            <td>مصاريف الأطفال</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_children" value="0" readonly disabled style="cursor: not-allowed; background: transparent; border-color: transparent;" />
                            </td>
                        </tr>
                        <tr>
                            <td>مصروفات أخرى بسيطة (ترفيه أو التزامات خفيفة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="single_other" value="600" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Incomes Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-income-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الثاني: الدخل والمزايا الشهرية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند والمزايا الوظيفية</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>الراتب الأساسي (حسب الوظيفة والخبرة كمصمم مبتدئ)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item basic-salary-input" data-key="single_basic" value="8500" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل انتقال (لتغطية تنقلات العمل)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="single_trans_allowance" value="1500" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل وجبة أو توفير وجبة (دعم عيني/نقدي للغذاء)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="single_meal_allowance" value="1000" />
                            </td>
                        </tr>
                        <tr>
                            <td>تأمين طبي (قيمة التغطية الطبية المقدمة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="single_medical" value="800" />
                            </td>
                        </tr>
                        <tr>
                            <td>حوافز وعمولات حسب الأداء الشهري</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="single_commission" value="1200" />
                            </td>
                        </tr>
                        <tr>
                            <td>مساعدة اجتماعية أو إعانة (عند الحاجة الاستثنائية)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="single_social" value="0" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="swvt-hr-summary-box">
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">إجمالي المصروفات الشهرية</span>
                <span class="swvt-hr-summary-value expense-total" style="color: #b32d2e;">11,500 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item" style="border-right: 1px solid #dcdcde; border-left: 1px solid #dcdcde;">
                <span class="swvt-hr-summary-label">إجمالي الدخل والمزايا</span>
                <span class="swvt-hr-summary-value income-total" style="color: #137333;">13,000 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">الفرق (الصافي القابل للادخار)</span>
                <span class="swvt-hr-summary-value net-diff" style="color: #2271b1;">+1,500 ج.م</span>
            </div>
        </div>

        <div class="swvt-hr-note-box">
            📌 <strong>تحليل اقتصادي للحالة:</strong> يعيش الموظف الأعزب هنا بمستوى معيشي متقشف نسبياً ولكنه متوازن. تمثل البدلات والعمولات جزءاً رئيسياً من سد الفجوة بين راتبه الأساسي والمصروفات. يتبقى له فائض بسيط كادخار استثماري أو لتطوير مهاراته للترقي لمرتبة وظيفية أعلى.
        </div>
    </div>

    <!-- TAB 2: MARRIED NO KIDS -->
    <div class="swvt-hr-tab-content" id="tab-married-no-kids">
        <div style="background: #fafbfe; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #eaf2fb;">
            <div style="font-weight: 700; color: #2271b1; font-size: 14.5px;">النموذج الثاني: موظف متزوج بدون أطفال</div>
            <div style="font-size: 12.5px; color: #50575e; margin-top: 5px; line-height: 1.5;">
                <strong>الوظيفة المفترضة:</strong> محاسب مالي أول (Senior Accountant) · 
                <strong>الخبرة:</strong> 5 سنوات · 
                <strong>الراتب الأساسي الحالي:</strong> <span class="basic-salary-display" style="font-weight: bold; color: #2c3338;">14,000</span> ج.م (محدد بناءً على الكفاءة والخبرة المهنية ودرجة إدارة الحسابات).
            </div>
        </div>

        <div class="swvt-hr-grid-cols">
            <!-- Expenses Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-expense-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الأول: المصروفات الشهرية الأساسية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند الأساسي للمصروفات</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>السكن أو المساهمة في السكن (إيجار شقة متوسطة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_rent" value="5500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الأكل والشرب (شخصين)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_food" value="7000" />
                            </td>
                        </tr>
                        <tr>
                            <td>المواصلات (انتقال الزوج والزوجة للعمل والمهام اليومية)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_transport" value="2500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الكهرباء والمياه والغاز (استهلاك شخصين)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_utilities" value="900" />
                            </td>
                        </tr>
                        <tr>
                            <td>الموبايل والإنترنت (إنترنت منزلي وهواتف للزوجين)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_mobile" value="600" />
                            </td>
                        </tr>
                        <tr>
                            <td>العلاج والطوارئ (كشوفات وأدوية غير مغطاة بالتأمين)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_medicine" value="1200" />
                            </td>
                        </tr>
                        <tr>
                            <td>الملابس والاحتياجات الشخصية للزوجين</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_clothes" value="1800" />
                            </td>
                        </tr>
                        <tr style="opacity: 0.6;">
                            <td>مصاريف الأطفال</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_children" value="0" readonly disabled style="cursor: not-allowed; background: transparent; border-color: transparent;" />
                            </td>
                        </tr>
                        <tr>
                            <td>مصروفات أخرى بسيطة (اجتماعيات وصيانة طفيفة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="married_other" value="1000" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Incomes Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-income-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الثاني: الدخل والمزايا الشهرية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند والمزايا الوظيفية</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>الراتب الأساسي (حسب الوظيفة والخبرة كمحاسب أول)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item basic-salary-input" data-key="married_basic" value="14000" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل انتقال (لتغطية تنقلات العمل)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="married_trans_allowance" value="2000" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل وجبة أو توفير وجبة (دعم معيشي)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="married_meal_allowance" value="1500" />
                            </td>
                        </tr>
                        <tr>
                            <td>تأمين طبي (قيمة التغطية للزوج والزوجة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="married_medical" value="1500" />
                            </td>
                        </tr>
                        <tr>
                            <td>حوافز وعمولات الأداء أو إغلاق الميزانيات</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="married_commission" value="2500" />
                            </td>
                        </tr>
                        <tr>
                            <td>مساعدة اجتماعية أو إعانة (دعم طارئ عند اللزوم)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="married_social" value="0" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="swvt-hr-summary-box">
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">إجمالي المصروفات الشهرية</span>
                <span class="swvt-hr-summary-value expense-total" style="color: #b32d2e;">20,500 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item" style="border-right: 1px solid #dcdcde; border-left: 1px solid #dcdcde;">
                <span class="swvt-hr-summary-label">إجمالي الدخل والمزايا</span>
                <span class="swvt-hr-summary-value income-total" style="color: #137333;">21,500 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">الفرق (الصافي القابل للادخار)</span>
                <span class="swvt-hr-summary-value net-diff" style="color: #2271b1;">+1,000 ج.م</span>
            </div>
        </div>

        <div class="swvt-hr-note-box">
            📌 <strong>تحليل اقتصادي للحالة:</strong> بالنسبة لأسرة تتكون من زوجين بدون أطفال، تزداد متطلبات السكن والأكل بشكل واضح. يساعد الترقي الوظيفي وزيادة الخبرة (محاسب أول) في زيادة الدخل الأساسي، إلا أن المصروفات تتسارع مما يجعل هامش الادخار ضيقاً جداً (1,000 جنيه فقط).
        </div>
    </div>

    <!-- TAB 3: MARRIED WITH KIDS -->
    <div class="swvt-hr-tab-content" id="tab-married-kids">
        <div style="background: #fafbfe; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #eaf2fb;">
            <div style="font-weight: 700; color: #2271b1; font-size: 14.5px;">النموذج الثالث: موظف متزوج ومعه طفلان</div>
            <div style="font-size: 12.5px; color: #50575e; margin-top: 5px; line-height: 1.5;">
                <strong>الوظيفة المفترضة:</strong> رئيس قسم المبيعات / مدير إدارة (Sales Team Lead / Manager) · 
                <strong>الخبرة:</strong> 8+ سنوات · 
                <strong>الراتب الأساسي الحالي:</strong> <span class="basic-salary-display" style="font-weight: bold; color: #2c3338;">25,000</span> ج.م (يعكس الكفاءة القيادية والخبرة الطويلة للدرجة الإدارية).
            </div>
        </div>

        <div class="swvt-hr-grid-cols">
            <!-- Expenses Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-expense-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الأول: المصروفات الشهرية الأساسية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند الأساسي للمصروفات</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>السكن أو المساهمة في السكن (شقة عائلية أو قسط تمويل)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_rent" value="7500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الأكل والشرب (4 أفراد)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_food" value="12500" />
                            </td>
                        </tr>
                        <tr>
                            <td>المواصلات والوقود وصيانة السيارة العائلية</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_transport" value="3500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الكهرباء والمياه والغاز والإنترنت المنزلي المشترك</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_utilities" value="1500" />
                            </td>
                        </tr>
                        <tr>
                            <td>الموبايل والإنترنت لأفراد الأسرة</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_mobile" value="800" />
                            </td>
                        </tr>
                        <tr>
                            <td>العلاج والطوارئ (عيادات وأدوية ومستلزمات الأطفال)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_medicine" value="2000" />
                            </td>
                        </tr>
                        <tr>
                            <td>الملابس والاحتياجات الشخصية (للوالدين والطفلين)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_clothes" value="3000" />
                            </td>
                        </tr>
                        <tr>
                            <td>مصاريف الأطفال (حضانة/مدارس، ترفيه ومستلزمات دراسية)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_children" value="6000" />
                            </td>
                        </tr>
                        <tr>
                            <td>مصروفات أخرى بسيطة (اجتماعيات ومناسبات طارئة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input expense-item" data-key="kids_other" value="1200" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Incomes Column -->
            <div>
                <h3 class="swvt-hr-section-title swvt-hr-income-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    القسم الثاني: الدخل والمزايا الشهرية
                </h3>
                <table class="swvt-hr-appraisal-table">
                    <thead>
                        <tr>
                            <th>البند والمزايا الوظيفية</th>
                            <th style="text-align: left; width: 150px;">القيمة التقديرية (ج.م)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>الراتب الأساسي (يعكس الكفاءة القيادية والدرجة الوظيفية)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item basic-salary-input" data-key="kids_basic" value="25000" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل انتقال (لتنقلات العمل أو صيانة سيارة الشركة)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="kids_trans_allowance" value="3000" />
                            </td>
                        </tr>
                        <tr>
                            <td>بدل وجبة أو توفير وجبة (دعم معيشي للمدراء)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="kids_meal_allowance" value="2000" />
                            </td>
                        </tr>
                        <tr>
                            <td>تأمين طبي عائلي (تغطية عائلية شاملة لـ 4 أفراد)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="kids_medical" value="3000" />
                            </td>
                        </tr>
                        <tr>
                            <td>حوافز وعمولات مبيعات فريق العمل</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="kids_commission" value="4000" />
                            </td>
                        </tr>
                        <tr>
                            <td>مساعدة اجتماعية أو إعانة (إعانة المدارس، موزعة شهرياً)</td>
                            <td style="text-align: left;">
                                <input type="number" class="swvt-hr-appraisal-input income-item" data-key="kids_social" value="1000" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary -->
        <div class="swvt-hr-summary-box">
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">إجمالي المصروفات الشهرية</span>
                <span class="swvt-hr-summary-value expense-total" style="color: #b32d2e;">38,000 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item" style="border-right: 1px solid #dcdcde; border-left: 1px solid #dcdcde;">
                <span class="swvt-hr-summary-label">إجمالي الدخل والمزايا</span>
                <span class="swvt-hr-summary-value income-total" style="color: #137333;">38,000 ج.م</span>
            </div>
            <div class="swvt-hr-summary-item">
                <span class="swvt-hr-summary-label">الفرق (الصافي القابل للادخار)</span>
                <span class="swvt-hr-summary-value net-diff" style="color: #2271b1;">0 ج.م</span>
            </div>
        </div>

        <div class="swvt-hr-note-box">
            📌 <strong>تحليل اقتصادي للحالة:</strong> يمثل هذا النموذج تحدياً كبيراً نظراً لكثافة الالتزامات المعيشية للأسرة المكونة من 4 أفراد في مصر، حيث تلتهم بنود الغذاء وإيجار السكن ومصاريف المدارس النصيب الأكبر من الدخل. على الرغم من أن راتب الموظف في الفئة العليا، فإن الدعم الاجتماعي والبدلات والعمولات هي التي تمنع العجز المالي وتوازن المعادلة ليكون الفائض صفراً.
        </div>
    </div>
</div>

<!-- Notification Toast -->
<div class="swvt-hr-toast" id="appraisal-toast">
    تم حفظ التعديلات بنجاح وجاري إعادة الاحتساب!
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching logic
    $('.swvt-hr-tab-btn').on('click', function() {
        var btn = $(this);
        var tabId = btn.data('tab');
        
        $('.swvt-hr-tab-btn').removeClass('is-active');
        $('.swvt-hr-tab-content').removeClass('is-active');
        
        btn.addClass('is-active');
        $('#' + tabId).addClass('is-active');
    });

    // Default values lookup
    var defaultValues = {
        // Single
        "single_rent": 3000, "single_food": 4000, "single_transport": 1500, "single_utilities": 500,
        "single_mobile": 400, "single_medicine": 500, "single_clothes": 1000, "single_children": 0, "single_other": 600,
        "single_basic": 8500, "single_trans_allowance": 1500, "single_meal_allowance": 1000, "single_medical": 800,
        "single_commission": 1200, "single_social": 0,
        // Married no kids
        "married_rent": 5500, "married_food": 7000, "married_transport": 2500, "married_utilities": 900,
        "married_mobile": 600, "married_medicine": 1200, "married_clothes": 1800, "married_children": 0, "married_other": 1000,
        "married_basic": 14000, "married_trans_allowance": 2000, "married_meal_allowance": 1500, "married_medical": 1500,
        "married_commission": 2500, "married_social": 0,
        // Married with kids
        "kids_rent": 7500, "kids_food": 12500, "kids_transport": 3500, "kids_utilities": 1500,
        "kids_mobile": 800, "kids_medicine": 2000, "kids_clothes": 3000, "kids_children": 6000, "kids_other": 1200,
        "kids_basic": 25000, "kids_trans_allowance": 3000, "kids_meal_allowance": 2000, "kids_medical": 3000,
        "kids_commission": 4000, "kids_social": 1000
    };

    // Calculate totals for a specific tab container (e.g. #tab-single)
    function calculateTabTotals(tabSelector) {
        var tab = $(tabSelector);
        var totalExpenses = 0;
        var totalIncome = 0;

        // Sum expenses
        tab.find('.expense-item').each(function() {
            var val = parseFloat($(this).val()) || 0;
            totalExpenses += val;
        });

        // Sum income
        tab.find('.income-item').each(function() {
            var val = parseFloat($(this).val()) || 0;
            totalIncome += val;
        });

        // Calculate difference
        var diff = totalIncome - totalExpenses;
        var diffText = (diff >= 0 ? '+' : '') + diff.toLocaleString('en-US') + ' ج.م';

        // Update UI
        tab.find('.expense-total').text(totalExpenses.toLocaleString('en-US') + ' ج.م');
        tab.find('.income-total').text(totalIncome.toLocaleString('en-US') + ' ج.م');
        
        var diffCell = tab.find('.net-diff');
        diffCell.text(diffText);

        if (diff > 0) {
            diffCell.css('color', '#2271b1');
        } else if (diff === 0) {
            diffCell.css('color', '#137333');
        } else {
            diffCell.css('color', '#b32d2e');
        }

        // Live update basic salary displays in introductory subheaders
        var basicVal = parseFloat(tab.find('.basic-salary-input').val()) || 0;
        tab.find('.basic-salary-display').text(basicVal.toLocaleString('en-US'));
    }

    // Run calculation for all tabs
    function runAllCalculations() {
        calculateTabTotals('#tab-single');
        calculateTabTotals('#tab-married-no-kids');
        calculateTabTotals('#tab-married-kids');
    }

    // Load saved values from local storage
    function loadSavedValues() {
        var saved = localStorage.getItem('swvt_hr_salary_appraisal_values');
        if (saved) {
            try {
                var vals = JSON.parse(saved);
                $('.swvt-hr-appraisal-input').each(function() {
                    var key = $(this).data('key');
                    if (vals[key] !== undefined) {
                        $(this).val(vals[key]);
                    }
                });
            } catch (e) {
                console.error("Error loading appraisal values", e);
            }
        }
        runAllCalculations();
    }

    // Save values to local storage
    function saveValues() {
        var vals = {};
        $('.swvt-hr-appraisal-input').each(function() {
            var key = $(this).data('key');
            if (key) {
                vals[key] = parseFloat($(this).val()) || 0;
            }
        });
        localStorage.setItem('swvt_hr_salary_appraisal_values', JSON.stringify(vals));
        
        // Show Toast
        var toast = $('#appraisal-toast');
        toast.text('تم حفظ التعديلات بنجاح وإعادة احتساب الأرقام!').fadeIn(200);
        setTimeout(function() {
            toast.fadeOut(300);
        }, 2500);
    }

    // Reset values to defaults
    function resetToDefaults() {
        if (confirm('هل أنت متأكد من رغبتك في إعادة تعيين جميع الجداول إلى القيم المعيشية الافتراضية؟')) {
            localStorage.removeItem('swvt_hr_salary_appraisal_values');
            $('.swvt-hr-appraisal-input').each(function() {
                var key = $(this).data('key');
                if (defaultValues[key] !== undefined) {
                    $(this).val(defaultValues[key]);
                }
            });
            runAllCalculations();
            
            var toast = $('#appraisal-toast');
            toast.text('تمت إعادة تعيين القيم الافتراضية للجداول!').fadeIn(200);
            setTimeout(function() {
                toast.fadeOut(300);
            }, 2500);
        }
    }

    // Hook inputs to live calculate on change
    $('.swvt-hr-appraisal-input').on('input change', function() {
        var tabId = $(this).closest('.swvt-hr-tab-content').attr('id');
        calculateTabTotals('#' + tabId);
    });

    // Save button click
    $('#swvt-hr-save-appraisal').on('click', function() {
        saveValues();
    });

    // Reset button click
    $('#swvt-hr-reset-appraisal').on('click', function() {
        resetToDefaults();
    });

    // Initial load
    loadSavedValues();
});
</script>

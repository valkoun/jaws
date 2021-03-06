<?php
/**
 * Meta Data
 *
 * "Project-Id-Version: Upgrade"
 * "Last-Translator: Ali Fazelzadeh <afz@php.net>"
 * "Language-Team: FA"
 * "MIME-Version: 1.0"
 * "Content-Type: text/plain; charset=UTF-8"
 * "Content-Transfer-Encoding: 8bit"
 */

define('_FA_UPGRADE_INTRODUCTION', "معرفی");
define('_FA_UPGRADE_AUTHENTICATION', "تاییدیه");
define('_FA_UPGRADE_REQUIREMENTS', "نیازمندیها");
define('_FA_UPGRADE_DATABASE', "دیتابیس");
define('_FA_UPGRADE_REPORT', "گزارش");
define('_FA_UPGRADE_VER_TO_VER', "{0} به {1}");
define('_FA_UPGRADE_SETTINGS', "تنظیمات");
define('_FA_UPGRADE_WRITECONFIG', "ثبت پیکربندی");
define('_FA_UPGRADE_FINISHED', "پایان");
define('_FA_UPGRADE_INTRO_WELCOME', "به ویزارد ارتقاء جاوز خوش آمدید.");
define('_FA_UPGRADE_INTRO_UPGRADER', "بروزرسانی جاوز، به شما در بروزسانی جاوز با نسخه جدید کمک میکند، لطفا قبل از هر چیز از دارا بودن موارد زیر مطمئن شوید");
define('_FA_UPGRADE_INTRO_DATABASE', "جزئیات ارتباط با دیتابیس - آدرس سرور، اسم کاربری، کلمه رمز و اسم دیتابیس");
define('_FA_UPGRADE_INTRO_FTP', "راهی برای آپلود فایلها، احتمالا FTP");
define('_FA_UPGRADE_INTRO_LOG', "ثبت گزارش مراحل بروزرسانی و خطاهای احتمالی در فایل ({0})");
define('_FA_UPGRADE_INTRO_LOG_ERROR', "اگر شما قصد ثبت گزارش مراحل بروزرسانی و خطاهای احتمالی را دارید، لطفا دسترسی به دایرکتوری ({0}) را به صورت قابل نوشتن تنظیم نمایید و این صفحه را دوباره بارگذاری نمایید. ");
define('_FA_UPGRADE_AUTH_PATH_INFO', "برای اطمینان از اینکه شما صاحب این سایت هستید، لطفا فایلی به نام {0} در دایرکتوری نصب جاوز ({1}) بسازید.");
define('_FA_UPGRADE_AUTH_UPLOAD', "برای آپلود فایل از همان طریقی که جاوز را آپلود کردید، اقدام نمایید.");
define('_FA_UPGRADE_AUTH_KEY_INFO', "فایل میبایستی فقط حاوی متن زیر باشد.");
define('_FA_UPGRADE_AUTH_ENABLE_SECURITY', "فعال سازی بروزرسانی جاوز در مد امن(توانمند شده با الگوریتم رمزنگاری RSA)");
define('_FA_UPGRADE_AUTH_ERROR_RSA_KEY_GENERATION', "خطا هنگام تولید کلید رمز. لطفا دوباره سعی کنید.");
define('_FA_UPGRADE_AUTH_ERROR_NO_MATH_EXTENSION', "خطا هنگام تولید کلید رمز. هیچکدام از الحاقی محاسباتی روی PHP فعال نیستند.");
define('_FA_UPGRADE_AUTH_ERROR_KEY_FILE', "فایل کلید ({0}) پیدا نشد، لطفا از ساخت آن و همچنین از قابل خواندن بودن آن مطمئن شوید.");
define('_FA_UPGRADE_AUTH_ERROR_KEY_MATCH', "متن فایل کلید ({0}) با متن زیر یکسان نمیباشد، از ورود صحیح متن زیر مطمئن شوید.");
define('_FA_UPGRADE_REQ_REQUIREMENT', "نیازمندیها");
define('_FA_UPGRADE_REQ_OPTIONAL', "موارد زیر ضروری نبوده ولی پیشنهاد میگردند");
define('_FA_UPGRADE_REQ_RECOMMENDED', "پیشنهادی");
define('_FA_UPGRADE_REQ_DIRECTIVE', "عنوان");
define('_FA_UPGRADE_REQ_ACTUAL', "واقعی");
define('_FA_UPGRADE_REQ_RESULT', "نتیجه");
define('_FA_UPGRADE_REQ_PHP_VERSION', "نسخه PHP");
define('_FA_UPGRADE_REQ_GREATER_THAN', "حداقل {0}");
define('_FA_UPGRADE_REQ_DIRECTORY', "دایرکتوری {0}");
define('_FA_UPGRADE_REQ_EXTENSION', "الحاقی {0}");
define('_FA_UPGRADE_REQ_FILE_UPLOAD', "آپلود فایل");
define('_FA_UPGRADE_REQ_SAFE_MODE', "حالت Safe mode");
define('_FA_UPGRADE_REQ_READABLE', "خواندنی");
define('_FA_UPGRADE_REQ_WRITABLE', "نوشتنی");
define('_FA_UPGRADE_REQ_OK', "تایید");
define('_FA_UPGRADE_REQ_BAD', "رد");
define('_FA_UPGRADE_REQ_OFF', "غیرفعال");
define('_FA_UPGRADE_REQ_ON', "فعال");
define('_FA_UPGRADE_REQ_RESPONSE_DIR_PERMISSION', "دایرکتوری {0} مشکل دسترسی (خواندنی، نوشتنی) دارد، لطفا مشکل آنرا مرتفع نمایید.");
define('_FA_UPGRADE_REQ_RESPONSE_PHP_VERSION', "حداقل نسخه مورد نیاز برای نصب جاوز، {0} میباشد، بنابراین میبایستی نسخه PHP خود را ارتقاء دهید.");
define('_FA_UPGRADE_REQ_RESPONSE_DIRS_PERMISSION', "دایرکتورهای زیر که با علامت ({0}) مشخص شده اند، نیاز است خواندنی یا نوشتنی باشند، لطفا مشکل دسترسیهای آنها را مرتفع سازید.");
define('_FA_UPGRADE_REQ_RESPONSE_EXTENSION', "الحاقی {0} برای استفاده از جاوز ضروری است.");
define('_FA_UPGRADE_DB_INFO', "شما الان در مرحله نصب دیتابیس هستید، که برای نگهداری اطلاعات و همچنین تنظیمات سایت شما، مورد استفاده قرار میگیرد.");
define('_FA_UPGRADE_DB_HOST', "آدرس سرور");
define('_FA_UPGRADE_DB_HOST_INFO', "اگر درباره آن چیزی نمیدانید، آنرا با {0} پر نمایید.");
define('_FA_UPGRADE_DB_DRIVER', "نوع دیتابیس");
define('_FA_UPGRADE_DB_USER', "نام کاربری");
define('_FA_UPGRADE_DB_PASS', "کلمه رمز");
define('_FA_UPGRADE_DB_IS_ADMIN', "آیا دسترسی سوپروایزری دارد؟");
define('_FA_UPGRADE_DB_NAME', "نام دیتابیس");
define('_FA_UPGRADE_DB_PATH', "مسیر دیتابیس");
define('_FA_UPGRADE_DB_PATH_INFO', "این فیلد در صورتی پر کنید که قصد تغییر مسیر دیتابیس در SQLite، Interbase و یا Firebird را داشته باشید.");
define('_FA_UPGRADE_DB_PORT', "پورت دیتابیس");
define('_FA_UPGRADE_DB_PORT_INFO', "این فیلد را فقط وقتی پر کنید که دیتابیس شما روی پورتی غیر از حالت پیش فرضش نصب شده باشد. البته به صورت معمول دیتابیس ها روی پورت پیش فرض نصب میشوند، لذا اگر اطلاع کافی در مورد آن ندارید آنرا بصورت خالی نگه دارید.");
define('_FA_UPGRADE_DB_PREFIX', "پیش نام جدول");
define('_FA_UPGRADE_DB_PREFIX_INFO', "کلمه ای که قبل از نام جداول قرار خواهد گرفت، بوسیله آن می توان بیش از یک جاوز را برروی یک دیتابیس نصب کرد، به عنوان مثال blog_");
define('_FA_UPGRADE_DB_RESPONSE_PATH', "مسیر دیتابیس وجود ندارد.");
define('_FA_UPGRADE_DB_RESPONSE_PORT', "پورت تنها می تواند شامل اعداد باشد.");
define('_FA_UPGRADE_DB_RESPONSE_INCOMPLETE', "پر کردن همه فیلدها (بغیر از مسیر دیتابیس، پیش نام جداول و پورت دیتابیس) اجباری است.");
define('_FA_UPGRADE_DB_RESPONSE_CONNECT_FAILED', "خطایی در هنگام اتصال به دیتابیس رخ داده است، لطفا جزئیات را بررسی نموده و دوباره سعی نمایید.");
define('_FA_UPGRADE_REPORT_INFO', "مقایسه جاوز نصب شده با این نسخه از جاوز {0}");
define('_FA_UPGRADE_REPORT_NOTICE', "در زیر لیستی از نسخه های جاوز را مشاهده میکنید که این نسخه توان بروزرسانی آنها را دارد.");
define('_FA_UPGRADE_REPORT_NEED', "نیاز به ارتقاء دارد");
define('_FA_UPGRADE_REPORT_NO_NEED', "نیاز به ارتقاء ندارد");
define('_FA_UPGRADE_REPORT_NO_NEED_CURRENT', "نیاز به ارتقاء ندارد(نسخه جاری)");
define('_FA_UPGRADE_REPORT_MESSAGE', "اگر نسخه نصب شده فعلی شما جزء لیست بالا باشد، به نسخه جاری ارتقاء، در غیر اینصورت این ویزارد خاتمه می یابد.");
define('_FA_UPGRADE_VER_INFO', "ارتقاء از نسخه {0} به {1} شامل موارد زیر است");
define('_FA_UPGRADE_VER_NOTES', "<strong>توجه:</strong> هنگامی که ویزارد ارتقاء از نسخه قبلی به نسخه جدید خاتمه یابد، فقط ابزارهای اصلی ارتقاء یافته اند، لذا برای بروزرسانی سایر ابزارها نظیر بلاگ، بنرهای تبلیغاتی و ... میبایستی وارد بخش مدیریت وب سایت خود شوید.");
define('_FA_UPGRADE_VER_RESPONSE_GADGET_FAILED', "اشکالی در نصب ابزار اصلی {0} رخ داده است.");
define('_FA_UPGRADE_CONFIG_INFO', "حالا شما نیاز دارید که فایل پیکربندی خود را ذخیره نمایید.");
define('_FA_UPGRADE_CONFIG_SOLUTION', "شما این کار را می توانید  از دو طریق انجام دهید");
define('_FA_UPGRADE_CONFIG_SOLUTION_PERMISSION', "{0} را قابل نوشتن کنید و برروی کلید بعدی کلیک نمایید، با اینکار اجازه می دهید تنظیمات بوسیله نصب کننده ذخیره شوند.");
define('_FA_UPGRADE_CONFIG_SOLUTION_UPLOAD', "محتویات کادر زیر را کپی کرده و در یک فایل الصاق نمایید و با نام {0} ذخیره کنید");
define('_FA_UPGRADE_CONFIG_RESPONSE_WRITE_FAILED', "یک خطای ناشناخته در هنگام نوشتن فایل پیکربندی رخ داده است.");
define('_FA_UPGRADE_CONFIG_RESPONSE_MAKE_CONFIG', "شما باید پوشه config را قابل نوشتن نمایید و یا {0} را خودتان ایجاد نماید. ");
define('_FA_UPGRADE_FINISH_INFO', "بروزرسانی وب سایت شما با موفقیت به پایان رسید!");
define('_FA_UPGRADE_FINISH_CHOICES', "حالا شما دو انتخاب دارید <a href=\"{0}\">دیدن سایت</a> یا <a href=\"{1}\">ورود به کنترل پنل </a>.");
define('_FA_UPGRADE_FINISH_MOVE_LOG', "تذکر: اگر شما ویژگی ثبت گزارش مراحل نصب را در اولین مرحله فعال نموده بودید ما به شما پیشنهاد می کنیم آنرا در مسیر دیگری ذخیره نمایید و سپس فایل مورد نظر را جابجا ویا حذف نمایید.");
define('_FA_UPGRADE_FINISH_THANKS', "از اینکه جاوز را برای استفاده انتخاب نموده اید، متشکریم!");

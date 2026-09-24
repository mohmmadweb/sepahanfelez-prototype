-- =====================================================================
-- سپاهان فلز — ستون‌ها و جدول‌های لازم برای نسخه‌ی بازطراحی‌شده
-- ۳۱ شهریور ۱۴۰۵
-- =====================================================================
--
-- روی این هاست migration اجرا نمی‌شود (نه SSH، نه شل — DEPLOYMENT.md)، پس
-- این فایل را در phpMyAdmin روی دیتابیس `ahanamnc_DB` اجرا کنید.
--
-- همه‌ی دستورها ایمن‌اند: هر بلوک اول وجود ستون/جدول را چک می‌کند، پس اجرای
-- دوباره‌ی فایل خطا نمی‌دهد و چیزی را خراب نمی‌کند.
--
-- سایت بدون این فایل هم کار می‌کند. چیزی که با اجرای آن فعال می‌شود:
--   ۱. امتیاز ستاره‌ای دیدگاه‌ها            (product_comments.rating)
--   ۲. ماشین‌حساب وزن دقیق                  (products.weight_per_unit / area_per_unit)
--   ۳. نشان «بازبینی» روی قیمت ناهم‌خوان     (products.needs_review)
--   ۴. کارشناسان فروش از پنل                (sales_reps + category_sales_rep)
--   ۵. نرخ کرایه از پنل                     (freight_rates)
-- =====================================================================

-- ---------------------------------------------------------------------
-- ۱. امتیاز دیدگاه
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_comments' AND COLUMN_NAME = 'rating') > 0,
  'SELECT ''product_comments.rating already exists''',
  'ALTER TABLE `product_comments` ADD COLUMN `rating` TINYINT UNSIGNED NULL AFTER `body`'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- ۲. وزن و مساحت هر واحد فروش
--    امروز وزن فقط به صورت متن در values ذخیره می‌شود («۲۲ کیلوگرم»، «1/200»)
--    و با متن نمی‌شود ضرب کرد. این دو ستون همان عدد را قابل محاسبه می‌کنند.
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'weight_per_unit') > 0,
  'SELECT ''products.weight_per_unit already exists''',
  'ALTER TABLE `products` ADD COLUMN `weight_per_unit` DECIMAL(12,3) NULL AFTER `unit`'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'area_per_unit') > 0,
  'SELECT ''products.area_per_unit already exists''',
  'ALTER TABLE `products` ADD COLUMN `area_per_unit` DECIMAL(12,3) NULL AFTER `weight_per_unit`'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- ۳. نشان بازبینی
--    برای ردیف‌هایی که قیمتشان با بقیه‌ی دسته هم‌خوان نیست (مثل دو رول توری
--    جوشی که ۱٬۹۰۰٬۰۰۰ ریال ثبت شده‌اند در حالی که هم‌خانواده‌هایشان
--    ۳۴ تا ۴۹ میلیون‌اند). صفحه به‌جای ادعای «ارزان‌ترین»، می‌گوید در حال
--    بازبینی است.
-- ---------------------------------------------------------------------
SET @s := (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'needs_review') > 0,
  'SELECT ''products.needs_review already exists''',
  'ALTER TABLE `products` ADD COLUMN `needs_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`'));
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- ۴. کارشناسان فروش
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales_reps` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)  NOT NULL,
  `role`       VARCHAR(80)  NULL,
  `ext`        VARCHAR(10)  NULL COMMENT 'داخلی تلفن دفتر',
  `photo`      VARCHAR(200) NULL,
  `hours`      VARCHAR(120) NULL,
  `whatsapp`   VARCHAR(120) NULL,
  `order`      INT UNSIGNED NOT NULL DEFAULT 1,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`), KEY `sales_reps_active_order` (`is_active`, `order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `category_sales_rep` (
  `category_id`  BIGINT UNSIGNED NOT NULL,
  `sales_rep_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`category_id`, `sales_rep_id`),
  KEY `csr_rep` (`sales_rep_id`),
  CONSTRAINT `csr_category` FOREIGN KEY (`category_id`)  REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `csr_rep_fk`   FOREIGN KEY (`sales_rep_id`) REFERENCES `sales_reps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- ۵. نرخ کرایه‌ی حمل
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `freight_rates` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `destination`  VARCHAR(120)    NOT NULL,
  `rate_per_ton` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ریال؛ صفر یعنی تحویل درب کارخانه/انبار',
  `order`        INT UNSIGNED    NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- پس از اجرا
-- =====================================================================
-- • کارشناسان و نرخ‌ها را در این جدول‌ها وارد کنید. کد (Redesign::rep و
--   Redesign::freight) هر وقت جدول ردیف داشته باشد آن را به داده‌ی
--   resources/redesign/content.php ترجیح می‌دهد؛ هیچ تغییر دیگری لازم نیست.
--
-- • برای پر کردن weight_per_unit از روی مقدارهای متنی موجود، این کوئری را
--   اول با SELECT ببینید و بعد به UPDATE تبدیل کنید — «/» در این داده
--   جداکننده‌ی اعشار است، نه تقسیم:
--
--   SELECT p.id, p.title, v.title AS weight_text
--     FROM products p
--     JOIN product_spec_value psv ON psv.product_id = p.id
--     JOIN specs s  ON s.id = psv.spec_id
--     JOIN `values` v ON v.id = psv.value_id
--    WHERE s.title LIKE '%وزن%';
-- =====================================================================

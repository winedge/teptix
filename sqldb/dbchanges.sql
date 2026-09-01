ALTER TABLE `orders` CHANGE `customer_id` `customer_id` INT NULL;

ALTER TABLE `coupon_usage_history` ADD `guestuser_id` INT NULL AFTER `appuser_id`;

ALTER TABLE `order_child` ADD `guestuser_id` INT NULL AFTER `customer_id`;

ALTER TABLE `orders` ADD `guestuser_id` INT NULL AFTER `customer_id`;

DROP TABLE IF EXISTS `guest_user`;
CREATE TABLE IF NOT EXISTS `guest_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
COMMIT;

ALTER TABLE `tickets` ADD `is_add_on` INT NOT NULL DEFAULT '0' COMMENT '0=no,1yes' AFTER `is_deleted`;
ALTER TABLE `guest_user` ADD `is_guest_user` INT NOT NULL DEFAULT '1' AFTER `updated_at`;

ALTER TABLE `tickets` ADD `tax_id` VARCHAR(255) NULL AFTER `is_add_on`;

ALTER TABLE `tax` ADD `is_default` TINYINT NOT NULL DEFAULT '0' AFTER `status`;

ALTER TABLE `tax` ADD `created_by` TINYINT NOT NULL DEFAULT '0' COMMENT '0=admin, 1=organizer' AFTER `is_default`;

ALTER TABLE `tax` ADD `updated_by` TINYINT NOT NULL DEFAULT '0' COMMENT '0=admin,1=organizer' AFTER `created_by`;

ALTER TABLE `orders` ADD `admin_revenue` INT NULL AFTER `org_pay_status`, ADD `org_revenue` INT NULL AFTER `admin_revenue`;
# Attempts
# related classes: Attempts
CREATE TABLE `attempts`(
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(10) NOT NULL,
  `ip` VARCHAR(15) NOT NULL,
  `agent` VARCHAR(255) NOT NULL,
  `timestamp` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `i_action` (`action`),
  INDEX `i_ip` (`ip`),
  INDEX `i_agent` (`agent`),
  INDEX `i_ts` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Authentication
# related classes: Auth, AuthMw
CREATE TABLE `auth_roles` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(20) NOT NULL,
  UNIQUE (`role`),
  PRIMARY KEY (`id`),
  INDEX `i_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_actions`(
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(20) NOT NULL,
  UNIQUE (`action`),
  PRIMARY KEY (`id`),
  INDEX `i_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_roles_actions`(
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` TINYINT UNSIGNED NOT NULL,
  `action_id` TINYINT UNSIGNED NOT NULL,
  UNIQUE INDEX `u_pair` (`role_id`, `action_id`),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_role_id` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`),
  CONSTRAINT `fk_action_id` FOREIGN KEY (`action_id`) REFERENCES `auth_actions` (`id`),
  INDEX `i_role_id` (`role_id`),
  INDEX `i_action_id` (`action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Users
# Status:
# 0 - inactive
# 1 - activated
# 2 - expired
# 3 - user requested deletion
CREATE TABLE `auth_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` TINYINT UNSIGNED NOT NULL,
  `email` VARCHAR(60) NOT NULL,
  `pswd` CHAR(64) NULL,
  `signup_ts` INT,
  `status` TINYINT UNSIGNED,
  PRIMARY KEY (`id`),
  CONSTRAINT `fku_role_id` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`),
  INDEX `i_email` (`email`),
  INDEX `i_pswd` (`pswd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_users_social` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `provider` VARCHAR(15) NOT NULL,
  UNIQUE INDEX `u_user` (`user_id`, `provider`),
  PRIMARY KEY (`id`),
  CONSTRAINT `fkus_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_users_ban` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `ip_v4` VARCHAR(15) NULL,
  `till_ts` INT,
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_ip_v4` (`ip_v4`),
  INDEX `i_till_ts` (`till_ts`),
  CONSTRAINT `fkub_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence#title.2
CREATE TABLE `auth_tokens_activation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fkta_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_re_activation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktra_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_pairing_google` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktpg_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_re_pairing_google` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktrg_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_pairing_facebook` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktpf_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_re_pairing_facebook` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktrf_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_pairing_twitter` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktpt_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_re_pairing_twitter` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktrt_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_permanent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fkatp_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_pswd_renewal` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktpr_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_tokens_deletion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(12),
  `token` CHAR(64),
  `expires` INT,
  UNIQUE (`selector`),
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_selector` (`selector`),
  INDEX `i_expires` (`expires`),
  CONSTRAINT `fktd_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Inserts
INSERT INTO `auth_roles` (role) VALUES ('user');
INSERT INTO `auth_roles` (role) VALUES ('contributor');
INSERT INTO `auth_roles` (role) VALUES ('webmaster');
INSERT INTO `auth_roles` (role) VALUES ('admin');
INSERT INTO `auth_actions` (action) VALUES ('access-account');
INSERT INTO `auth_actions` (action) VALUES ('access-admin');
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (1, 1);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (2, 1);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (2, 2);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (3, 1);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (3, 2);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (4, 1);
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (4, 2);
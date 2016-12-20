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
# 1 - active
# 2 - expired
# 3 - forbidden
CREATE TABLE `auth_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` TINYINT UNSIGNED NOT NULL,
  `email` VARCHAR(60) NOT NULL,
  `pswd` CHAR(64) NOT NULL,
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
  `provider_key` VARCHAR(128) NULL,
  `provider` VARCHAR(15) NOT NULL,
  UNIQUE INDEX `u_user` (`user_id`, `provider`),
  PRIMARY KEY (`id`),
  CONSTRAINT `fks_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `auth_users_suspended` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `till_ts` INT,
  PRIMARY KEY (`id`),
  INDEX `i_user_id` (`user_id`),
  INDEX `i_till_ts` (`till_ts`),
  CONSTRAINT `fkss_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Steps:
# -> User receives email with activation link
# -> User confirms activation by clicking the activation link
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
  CONSTRAINT `fka_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence#title.2
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
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Steps:
# -> User sends password request by submitting the email address
# -> User receives email with link to password change page
# -> User sets new password on that page
CREATE TABLE `auth_tokens_password` (
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
  CONSTRAINT `fkpswd_user_id` FOREIGN KEY (`user_id`) REFERENCES `auth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

# Inserts
INSERT INTO `auth_roles` (role) VALUES ('user');
INSERT INTO `auth_actions` (action) VALUES ('access-account');
INSERT INTO `auth_roles_actions` (`role_id`, `action_id`) VALUES (1, 1);
#INSERT INTO auth_users (role_id, email, pswd, signup_ts) VALUES (1, 'jiri@mihal.me', '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08', UNIX_TIMESTAMP());
#INSERT INTO auth_users_activated (user_id) VALUES (1);
#INSERT INTO auth_tokens_activation (user_id, selector, token, expires) VALUES (1, )
# Users
# Status:
# 0 - inactive
# 1 - active
# 2 - disabled (deleted)
# 3 - banned

# Attempts
CREATE TABLE `attempts`(
  `id` INT NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(10) NOT NULL,
  `ip` VARCHAR(15) NOT NULL,
  `agent` VARCHAR(255) NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `i_ip` (`ip`),
  INDEX `i_agent` (`agent`),
  INDEX `i_date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
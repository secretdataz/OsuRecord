CREATE TABLE `beatmaps` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`osu_id` INT(10) UNSIGNED NOT NULL,
	`filename` VARCHAR(255) NOT NULL,
	`title` VARCHAR(255) NOT NULL,
	`artist` VARCHAR(255) NOT NULL,
	`creator` VARCHAR(255) NOT NULL,
	`size` INT(11) UNSIGNED NULL DEFAULT NULL,
	`osufiles` MEDIUMTEXT NOT NULL,
	`zip` TINYINT(3) UNSIGNED NOT NULL,
	`lastused` INT(10) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `filename` (`filename`),
	INDEX `title` (`title`),
	INDEX `artist` (`artist`),
	INDEX `creator` (`creator`),
	INDEX `lastused` (`lastused`),
	INDEX `size` (`size`),
	INDEX `osu_id` (`osu_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;


CREATE TABLE `record_log` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`date` DATETIME NOT NULL,
	`finished` DATETIME NOT NULL,
	`beatmap_id` INT(10) UNSIGNED NOT NULL,
	`difficulty_id` INT(10) UNSIGNED NOT NULL,
	`osu_id` INT(10) UNSIGNED NOT NULL,
	`osu_username` VARCHAR(255) NOT NULL,
	`maphash` VARCHAR(255) NOT NULL,
	`ip` VARCHAR(64) NOT NULL,
	`youtube_session` VARCHAR(255) NOT NULL,
	`youtube_url` VARCHAR(255) NOT NULL,
	`youtube_user` VARCHAR(255) NOT NULL,
	`osr_content` MEDIUMBLOB NOT NULL,
	`success` TINYINT(3) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;


CREATE TABLE `vars` (
	`last_beatmap_check` DATE NOT NULL
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
MAX_ROWS=1;

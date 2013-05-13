DROP TABLE IF EXISTS qotd_settings;
DROP TABLE IF EXISTS qotd_quotes;

CREATE TABLE qotd_settings (
	id    INT(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
	name  VARCHAR(200)          NOT NULL,
	value VARCHAR(200)          NOT NULL,

	PRIMARY KEY(id)
) ENGINE=InnoDB;

CREATE TABLE qotd_quotes (
	id     INT(11)      UNSIGNED NOT NULL AUTO_INCREMENT,
	quote  VARCHAR(200)          NOT NULL,
	author VARCHAR(200)          NOT NULL,
	last_usage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	
	PRIMARY KEY(id)
) ENGINE=InnoDB;


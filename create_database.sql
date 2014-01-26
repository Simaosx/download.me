	CREATE TABLE files (
		id INT unsigned auto_increment NOT NULL primary key,
		name VARCHAR(100) NOT NULL,
		md5_name VARCHAR(255) NOT NULL COMMENT 'имя в md5 под которым файл сохраняется на диск',
		mime VARCHAR(50) NOT NULL,
		unixtime INT unsigned NOT NULL,
		size INT unsigned NOT NULL, 
		path VARCHAR(255),
		description TEXT DEFAULT NULL,
		FULLTEXT (name, description)
		) ENGINE=InnoDB default charset=utf8;
		
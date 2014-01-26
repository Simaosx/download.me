CREATE TABLE comments(
	    id INT unsigned auto_increment NOT NULL PRIMARY KEY,
	    parent_id INT unsigned NULL,
		file_id INT unsigned NOT NULL,
		unixtime INT unsigned NOT NULL,
		author VARCHAR(50) NOT NULL,
		body TEXT NOT NULL,
		path VARCHAR(100) NOT NULL,
		 FOREIGN KEY (file_id) REFERENCES files(id)
		 ON DELETE CASCADE
		 ON UPDATE CASCADE,
		 FOREIGN KEY (parent_id) REFERENCES comments(id)
		 ON DELETE CASCADE
		 ON UPDATE CASCADE
		) ENGINE=InnoDB charset=utf8;
CREATE INDEX pth_ind ON comments (path); 
CREATE TABLE df_user (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(128) NOT NULL,
    password VARCHAR(128) NOT NULL,
    email VARCHAR(128) NOT NULL,
    full_name VARCHAR(80) NOT NULL,
    first_name VARCHAR(40) NOT NULL,
    last_name VARCHAR(40) NOT NULL,
    phone VARCHAR(16),
    active BOOLEAN NOT NULL DEFAULT 1,
    admin BOOLEAN NOT NULL DEFAULT 0,
    confirm_code VARCHAR(40),
    role_id INT,
    security_question VARCHAR(160),
    security_answer VARCHAR(80),
    created_date DATETIME NOT NULL DEFAULT NOW(),
    last_modified_date DATETIME NOT NULL DEFAULT NOW(),
    created_by_id INT,
    last_modified_by_id INT
);

CREATE TABLE df_role (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(128) NOT NULL,
    description VARCHAR(8000),
    app_ids VARCHAR(8000),
    created_date DATETIME NOT NULL DEFAULT NOW(),
    last_modified_date DATETIME NOT NULL DEFAULT NOW(),
    created_by_id INT,
    last_modified_by_id INT
);

CREATE TABLE df_role_service_access (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    role_id INT NOT NULL,
    service_id INT,
    service VARCHAR(128) NOT NULL,
    component VARCHAR(128) NOT NULL,
    read BOOLEAN NOT NULL DEFAULT 0,
    create BOOLEAN NOT NULL DEFAULT 0,
    update BOOLEAN NOT NULL DEFAULT 0,
    delete BOOLEAN NOT NULL DEFAULT 0
);

CREATE TABLE df_service (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(128) NOT NULL,
    label VARCHAR(128),
    active BOOLEAN NOT NULL DEFAULT 1,
    type VARCHAR(40) NOT NULL,
    native_format VARCHAR(40),
    base_url VARCHAR(255),
    params VARCHAR(8000),
    headers VARCHAR(8000),
    created_date DATETIME NOT NULL DEFAULT NOW(),
    last_modified_date DATETIME NOT NULL DEFAULT NOW(),
    created_by_id INT,
    last_modified_by_id INT
);

CREATE TABLE df_app (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(128) NOT NULL,
    label VARCHAR(128) NOT NULL,
    description VARCHAR(4000),
    active BOOLEAN NOT NULL DEFAULT 1,
    url VARCHAR(4000),
    is_url_external BOOLEAN NOT NULL DEFAULT 0,
    app_group_ids VARCHAR(4000),
    created_date DATETIME NOT NULL DEFAULT NOW(),
    last_modified_date DATETIME NOT NULL DEFAULT NOW(),
    created_by_id INT,
    last_modified_by_id INT
);

CREATE TABLE df_app_group (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(128) NOT NULL,
    label VARCHAR(128) NOT NULL,
    description VARCHAR(4000),
    created_date DATETIME NOT NULL DEFAULT NOW(),
    last_modified_date DATETIME NOT NULL DEFAULT NOW(),
    created_by_id INT,
    last_modified_by_id INT
);

CREATE TABLE df_label (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    table VARCHAR(128) DEFAULT '',
    field VARCHAR(128) DEFAULT '',
    picklist VARCHAR(8000) DEFAULT '',
    label VARCHAR(128) DEFAULT '',
    plural VARCHAR(128) DEFAULT ''
);

CREATE TABLE df_session (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    start_time INT NOT NULL,
    data VARCHAR(4000) NOT NULL
);

CREATE TABLE tbl_user (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(128) NOT NULL,
    password VARCHAR(128) NOT NULL,
    email VARCHAR(128) NOT NULL
);

INSERT INTO tbl_user (username, password, email) VALUES ('test1', 'pass1', 'test1@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test2', 'pass2', 'test2@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test3', 'pass3', 'test3@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test4', 'pass4', 'test4@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test5', 'pass5', 'test5@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test6', 'pass6', 'test6@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test7', 'pass7', 'test7@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test8', 'pass8', 'test8@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test9', 'pass9', 'test9@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test10', 'pass10', 'test10@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test11', 'pass11', 'test11@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test12', 'pass12', 'test12@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test13', 'pass13', 'test13@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test14', 'pass14', 'test14@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test15', 'pass15', 'test15@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test16', 'pass16', 'test16@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test17', 'pass17', 'test17@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test18', 'pass18', 'test18@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test19', 'pass19', 'test19@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test20', 'pass20', 'test20@example.com');
INSERT INTO tbl_user (username, password, email) VALUES ('test21', 'pass21', 'test21@example.com');

CREATE TABLE tt_content (
	tx_accessibility_barrier_free_name varchar(255) DEFAULT '' NOT NULL,
	tx_accessibility_barrier_free_address varchar(255) DEFAULT '' NOT NULL,
	tx_accessibility_barrier_free_phone varchar(255) DEFAULT '' NOT NULL,
	tx_accessibility_barrier_free_email varchar(255) DEFAULT '' NOT NULL,
	tx_accessibility_barrier_free_contactFormLink varchar(255) DEFAULT '' NOT NULL,
	tx_accessibility_barrier_free_notBarrierFreeContent text,
	tx_accessibility_barrier_free_economic_unreasonable text,
	tx_accessibility_barrier_free_addressOfTheEnforcementBody text
);

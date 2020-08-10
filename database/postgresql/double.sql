ALTER TABLE ONLY trends
	ALTER COLUMN value_min TYPE DOUBLE PRECISION,
	ALTER COLUMN value_min SET DEFAULT '0.0000',
	ALTER COLUMN value_avg TYPE DOUBLE PRECISION,
	ALTER COLUMN value_avg SET DEFAULT '0.0000',
	ALTER COLUMN value_max TYPE DOUBLE PRECISION,
	ALTER COLUMN value_max SET DEFAULT '0.0000';
ALTER TABLE ONLY history
	ALTER COLUMN value TYPE DOUBLE PRECISION,
	ALTER COLUMN value SET DEFAULT '0.0000';
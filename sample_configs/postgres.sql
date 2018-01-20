CREATE USER myradio WITH password 'myradio';
ALTER USER myradio CREATEDB;
CREATE DATABASE myradio WITH OWNER=myradio;

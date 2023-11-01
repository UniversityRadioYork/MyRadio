CREATE TYPE deletion AS ENUM ('default', 'informed', 'optout', 'deleted');

ALTER TABLE Public.member
ADD data_removal deletion DEFAULT('default');

ALTER TABLE Public.member
ADD hide_profile boolean DEFAULT(false);

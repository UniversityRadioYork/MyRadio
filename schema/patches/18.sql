CREATE TYPE deletion AS ENUM ('default', 'informed', 'optout', 'deleted');

ALTER TABLE public.member
    ADD gdpr_accepted boolean default(false);

ALTER TABLE Public.member
ADD data_removal deletion DEFAULT('default');

ALTER TABLE Public.member
ADD hide_profile boolean DEFAULT(false);

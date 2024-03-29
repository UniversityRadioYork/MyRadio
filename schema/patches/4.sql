BEGIN;

-- Mailing lists used to be show/hide. Now you can define their listed order
--  To hide, set the "ordering" to a negative number.
ALTER TABLE public.mail_list
    ADD COLUMN ordering INTEGER DEFAULT 0;

COMMENT ON COLUMN public.mail_list.ordering
    IS 'The order in which to display these lists in MyRadio. Ascending, equal values sorted by ID. Set to negative to hide from MyRadio.';

ALTER TABLE public.mail_list
    DROP COLUMN IF EXISTS current;

COMMIT;

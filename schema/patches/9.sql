-- Link show tracklists to selector sources
--  Moved from base.sql into a separate patch
--  TODO Explain this better
CREATE TABLE selsources (
    selaction integer NOT NULL,
    sourceid character(1) NOT NULL
);
COMMENT ON TABLE selsources IS 'Marries selector actions with tracklist sources';

--
-- Name: selsources_sourceid_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY selsources
    ADD CONSTRAINT selsources_sourceid_fkey FOREIGN KEY (sourceid) REFERENCES tracklist.source(sourceid);


--
-- Name: selsources_selaction_fkey; Type: FK CONSTRAINT; Schema: tracklist
--

ALTER TABLE ONLY selsources
    ADD CONSTRAINT selsources_selaction_fkey FOREIGN KEY (selaction) REFERENCES public.selector_actions(action);

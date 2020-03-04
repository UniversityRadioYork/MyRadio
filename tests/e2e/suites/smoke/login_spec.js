describe("Login", () => {
    it("Redirects to the login screen if not logged in", () => {
        cy.request({
            url: "http://localhost:7080/myradio",
            followRedirect: true
        }).then(resp => {
            expect(resp.status).to.eq(302);
            expect(resp.redirectedToUrl).to.eq("http://localhost:7080/myradio/MyRadio/login?next=/myradio");
        });
    });
});
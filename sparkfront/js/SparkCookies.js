/**
 * The visitor's cookie choice - analytics and ads, each allowed or not (the necessary cookies need no consent).
 *
 * The choice is stored in the cookie SparkGTM.CONSENT_COOKIE ({"analytics":bool,"ads":bool}, a year) and sent to
 * Google (consent 'update') and the Facebook pixel (consent grant / revoke) when it is made; SparkGTM applies the
 * stored choice on every later page before anything is sent. Nothing is sent before a choice.
 *
 * The panel (the store frame: StoreCookies) is the element '.section.cookies': attribute accepted="false" shows it,
 * "true" hides it; attribute settings="true" shows the choices (checkboxes name='analytics' / name='ads').
 * Its buttons call acceptAll(), rejectAll(), showSettings(), saveSettings(); open() shows it again (the footer).
 */
class SparkCookies extends SparkObject
{
    constructor() {
        super();
        SparkCookies.COOKIE_ATTRIBUTES = {
            expires: 365,           // days
            path: document.Spark.LOCAL || "/",
            secure: location.protocol === "https:",
            sameSite: 'Lax'
        };
        //the cookies of the earlier panel (consent by continuing to browse) - not a valid choice, asked again
        SparkCookies.OLD_COOKIES = ["accept_cookies", "consent_user"];

        onPageLoad(() => this.updateCookiesPanel());
    }

    /**
     * The stored choice {analytics, ads} or null
     * @returns {{analytics: boolean, ads: boolean}|null}
     */
    choice() {
        const value = Cookies.get(SparkGTM.CONSENT_COOKIE);
        if (!value) return null;
        try {
            const stored = JSON.parse(value);
            return {analytics: !!stored.analytics, ads: !!stored.ads};
        }
        catch (e) {
            return null;
        }
    }

    isAccepted() {
        return this.choice() !== null;
    }

    acceptAll() {
        this.choose(true, true);
    }

    rejectAll() {
        this.choose(false, false);
    }

    /**
     * The choice of the checkboxes of the panel
     */
    saveSettings() {
        const panel = this.panel();
        const checked = (name) => !!panel?.querySelector("input[name='" + name + "']")?.checked;
        this.choose(checked("analytics"), checked("ads"));
    }

    /**
     * Store the choice and send it
     * @param analytics {boolean}
     * @param ads {boolean}
     */
    choose(analytics, ads) {
        SparkCookies.OLD_COOKIES.forEach((name) => Cookies.remove(name, {path: SparkCookies.COOKIE_ATTRIBUTES.path}));
        Cookies.set(SparkGTM.CONSENT_COOKIE, JSON.stringify({analytics: analytics, ads: ads}), SparkCookies.COOKIE_ATTRIBUTES);

        let consent = new SparkEvent(SparkEvent.GTM_EVENT, null);
        consent.gtm = {command: "consent", type: "update", parameters: SparkGTM.ConsentSignals(analytics, ads)};
        document.dispatchEvent(consent);

        if (typeof window.fbq === "function") {
            window.fbq("consent", ads ? "grant" : "revoke");
        }

        //a trigger for tags that wait for the choice (Google Tag Manager)
        let event = new SparkEvent(SparkEvent.GTM_EVENT, null);
        event.gtm = {command: "event", type: "consent_updated", parameters: {analytics: analytics, ads: ads}};
        document.dispatchEvent(event);

        this.updateCookiesPanel();
    }

    /**
     * @returns {HTMLElement|null}
     */
    panel() {
        return document.querySelector(".section.cookies");
    }

    showSettings() {
        const panel = this.panel();
        if (!panel) return;
        const choice = this.choice();
        const analytics = panel.querySelector("input[name='analytics']");
        const ads = panel.querySelector("input[name='ads']");
        if (analytics) analytics.checked = choice ? choice.analytics : false;
        if (ads) ads.checked = choice ? choice.ads : false;
        panel.setAttribute("settings", "true");
    }

    /**
     * Show the panel again with the choices (the 'cookies' link of the footer)
     */
    open() {
        const panel = this.panel();
        if (!panel) return;
        this.showSettings();
        panel.setAttribute("accepted", "false");
    }

    updateCookiesPanel() {
        const panel = this.panel();
        if (!panel) return;
        panel.setAttribute("accepted", this.isAccepted() ? "true" : "false");
        panel.setAttribute("settings", "false");
    }

    /**
     * @deprecated the earlier panel's single button - now acceptAll()
     */
    accept() {
        this.acceptAll();
    }
}

document.sparkCookies = new SparkCookies();

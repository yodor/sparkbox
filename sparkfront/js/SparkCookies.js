/**
 * Cookies consent manager
 */

class SparkCookies extends SparkObject
{

    constructor() {
        super();
        SparkCookies.COOKIE_ATTRIBUTES = {
            expires: 365,           // days
            path: document.Spark.LOCAL,
            secure: true,           // must be true on HTTPS
            sameSite: 'Lax'         // or 'Strict' depending on requirements
        };
    }

    accept(personalized) {
        Cookies.set('accept_cookies', 1, SparkCookies.COOKIE_ATTRIBUTES);

        let consent_user = SparkGTM.DefaultConsent;

        if (personalized) {
            consent_user = personalized;
        }

        Cookies.set('consent_user', JSON.stringify(consent_user), SparkCookies.COOKIE_ATTRIBUTES);

        this.checkAccepted();
        this.updateCookiesPanel();

    }

    isAccepted() {
        if (Cookies.get('accept_cookies')>0) return true;
        return false;
    }

    checkAccepted() {

    }

    updateCookiesPanel()
    {
        let isAccepted = this.isAccepted();
        document.querySelector(".section.cookies").setAttribute("accepted", isAccepted);

        let consent_update = SparkGTM.DefaultConsent;

        let consent_user = Cookies.get('consent_user');
        if (consent_user) {
            consent_update = JSON.parse(consent_user);
        }

        let event_consent = new SparkEvent(SparkEvent.GTM_EVENT, null);
        event_consent.gtm = {command:"consent", type:"update", parameters:consent_update};
        document.dispatchEvent(event_consent);

        let event = new SparkEvent(SparkEvent.GTM_EVENT, null);
        event.gtm = {command:"event", type:"consent_updated"};
        document.dispatchEvent(event);

    }
}

document.sparkCookies = new SparkCookies();
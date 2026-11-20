/**
 * Google tag (gtag.js) with Consent Mode v2.
 *
 * Loaded before gtag.js and the 'config' commands: every consent signal starts 'denied' (no analytics or ad
 * cookies before a choice), and the visitor's stored choice (SparkCookies) is applied at once - before the
 * first page view is sent. A choice made on the page is sent by SparkCookies as a 'consent' 'update'.
 *
 * Other scripts send gtag commands as SparkEvent.GTM_EVENT with event.gtm = {command, type, parameters}.
 */
class SparkGTM {

    /**
     * Before a choice: nothing stored, nothing sent for ads; Google models the rest
     */
    static DefaultConsent = {
        "ad_storage": "denied",          //ad cookies
        "ad_user_data": "denied",        //user data sent to Google for ads
        "ad_personalization": "denied",  //ad personalization (remarketing)
        "analytics_storage": "denied"    //analytics cookies
    };

    /**
     * The name of the cookie holding the visitor's choice (SparkCookies writes it)
     */
    static CONSENT_COOKIE = "cookie_consent";

    /**
     * The stored choice as gtag consent signals, or null when the visitor has not chosen yet
     * @returns {Object|null}
     */
    static StoredConsent() {
        const match = document.cookie.match(new RegExp("(?:^|; )" + SparkGTM.CONSENT_COOKIE + "=([^;]*)"));
        if (!match) return null;
        try {
            const stored = JSON.parse(decodeURIComponent(match[1]));
            return SparkGTM.ConsentSignals(!!stored.analytics, !!stored.ads);
        }
        catch (e) {
            return null;
        }
    }

    /**
     * The gtag consent signals of a choice
     * @param analytics {boolean} analytics cookies allowed
     * @param ads {boolean} ad cookies and ad data allowed
     * @returns {Object}
     */
    static ConsentSignals(analytics, ads) {
        const value = (allowed) => allowed ? "granted" : "denied";
        return {
            "ad_storage": value(ads),
            "ad_user_data": value(ads),
            "ad_personalization": value(ads),
            "analytics_storage": value(analytics)
        };
    }

    /**
     * Ad / marketing cookies allowed by the stored choice (the Facebook pixel reads it too)
     * @returns {boolean}
     */
    static AdsGranted() {
        const consent = SparkGTM.StoredConsent();
        return !!consent && consent.ad_storage === "granted";
    }

    constructor() {

        gtag('consent', 'default', Object.assign({}, SparkGTM.DefaultConsent, {"wait_for_update": 500}));

        const stored = SparkGTM.StoredConsent();
        if (stored) {
            gtag('consent', 'update', stored);
        }

        gtag('js', new Date());

        document.addEventListener(SparkEvent.GTM_EVENT, (event) => this.handleEvent(event));
    }

    /**
     * SparkEvent
     * @param event {SparkEvent}
     */
    handleEvent(event) {

        if (! (event instanceof SparkEvent)) {
            console.log("Not a SparkEvent");
            return;
        }
        try {
            if (!event.gtm) throw "No gtm set";
            //GTMCommand
            const gtm = event.gtm;

            if (!gtm.command || !gtm.type ) throw "Not a valid GTMCommand object";

            if (!gtm.parameters) {
                gtag(gtm.command, gtm.type);
            } else {
                gtag(gtm.command, gtm.type, gtm.parameters);
            }
        }
        catch (e) {
            console.log(e);
        }
    }

    /**
     *
     * @param conversionID {string} 'AW-123456789/abcDEF123_456GHI789'
     */
    emitConversion(conversionID) {
        if (conversionID) {
            gtag('event','conversion', {
                send_to: conversionID
            });
        }
    }

}


window.dataLayer = window.dataLayer || [];
function gtag() {
    window.dataLayer.push(arguments);
}

document.sparkGTM = new SparkGTM();

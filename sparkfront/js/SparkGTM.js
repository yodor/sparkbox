class SparkGTM {
    static DefaultConsent = {
        "ad_user_data": "denied", //Whether user data can be sent to Google for advertising purposes
        "ad_personalization": "denied", //Whether data can be used for ad personalization
        "ad_storage": "granted", //Whether ad cookies can be read/written
        "analytics_storage": "granted" //Whether analytics cookies can be read/written
    }
    constructor() {

        gtag('consent', 'default', SparkGTM.DefaultConsent);

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
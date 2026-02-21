class SparkGTM {

    constructor() {
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
gtag('js', new Date());

document.sparkGTM = new SparkGTM();
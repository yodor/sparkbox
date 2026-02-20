class SparkGTM {

    constructor() {
        window.dataLayer = window.dataLayer || [];
        document.addEventListener(SparkEvent.GTM_EVENT, (event) => this.handleEvent(event));
    }


    gtag() {
        // console.log(arguments);
        window.dataLayer.push(arguments);
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

            if (!gtm.command || !gtm.type || !gtm.parameters) throw "Not a valid GTMCommand object";

            const command = gtm.command; //string 'event'
            const type = gtm.type; //string 'conversion'
            const parameters = gtm.parameters; //object { send_to: 'AW-123456789/abcDEF123_456GHI789'}

            this.gtag(command, type, parameters);
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
            this.gtag('event','conversion', {
                send_to: conversionID
            });
        }
    }

}

document.sparkGTM = new SparkGTM();
onPageLoad(function() {
    document.sparkGTM.gtag('js', new Date());
});
function gtag(arguments) {
    document.sparkGTM.gtag(arguments);
}
class SparkCookies extends SparkObject
{
    constructor() {
        super();
        this.defaultCookies = {
            "ad_user_data": "denied",
            "ad_personalization": "denied",
            "ad_storage": "granted",
            "analytics_storage": "granted"
        };
        this.acceptedCookies = {
            "ad_user_data": "denied",
            "ad_personalization": "denied",
            "ad_storage": "granted",
            "analytics_storage": "granted"
        };
    }

    accept() {
        Cookies.set('accept_cookies', 1, { expires: 3650 });
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

        let event = new SparkEvent(SparkEvent.GTM_EVENT, null);

        if (isAccepted) {
            event.gtm = {command:"consent", type:"update", parameters:this.defaultCookies};
        }
        else {
            event.gtm = {command:"consent", type:"update", parameters:this.acceptedCookies};
        }
        document.dispatchEvent(event);
    }
}
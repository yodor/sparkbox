class LoginForm extends Component {
    constructor() {
        super();
        this.setClass("FORM");
    }

    initialize() {

        try {
            this.form = $(this.selector());
            if (!this.form.get(0)) throw "Required form component class not found";

            this.email = this.form.find("INPUT[name='email']").first();
            if (!this.email.get(0)) throw "Required field not found";

            this.password = this.form.find("INPUT[name='password']").first();
            if (!this.password.get(0)) throw "Required field not found";

            this.rand = this.form.find("INPUT[type='hidden'][name='rand']").first();
            if (!this.rand.get(0)) throw "Required field not found";

            this.pass = this.form.find("INPUT[type='hidden'][name='pass']").first();
            if (!this.pass.get(0)) throw "Required field not found";

            this.form.on("submit", function (event) {

                return this.onSubmit(event);

            }.bind(this));

        } catch (exx) {
            alert("Unable to attach with requested AuthForm: " + exx);
        }

    }

    onSubmit(event) {

        this.email.val(trim(this.email.val()));
        this.password.val(trim(this.password.val()));

        let u = this.email.val();
        let p = this.password.val();
        let r = this.rand.val();

        try {
            if (u.length < 1) {
                throw "Input your email";
            }
            this.processEmail();

            if (!(typeof hex_hmac_md5 === 'function')) throw "Required hashing function not defined";

            this.processPassword();

        } catch (exx) {

            showAlert(exx);
            return false;

        }


        return true;

    }

    async processPassword() {

        let p = this.password.val();
        let r = this.rand.val();

        if (p.length < 1) throw "Input your password to continue.";
        if (p.length < 6) throw "Minimum password of 6 symbols required.";

        this.password.val("");

        let result = hex_hmac_md5(hex_md5(p), r);
        //const result = await this.hmac(hmac_key, hmac_message);

        this.pass.val(result);
        this.rand.val("");

    }

    async hmac(hmac_key, hmac_message) {
        // encoder to convert string to Uint8Array
        var enc = new TextEncoder("utf-8");

        const key = await window.crypto.subtle.importKey(
            "raw", // raw format of the key - should be Uint8Array
            enc.encode(hmac_key),
            { // algorithm details
                name: "HMAC",
                hash: {name: "SHA-256"}
            },
            false, // export = false
            ["sign", "verify"] // what this key can do
        );
        const signature =  await  window.crypto.subtle.sign(
            "HMAC",
            key,
            enc.encode(hmac_message)
        );

        let signature_uint8 = new Uint8Array(signature);
        //hex
        return Array.prototype.map.call(signature_uint8, x => x.toString(16).padStart(2, '0')).join("");

    }

    processEmail() {
        let u = this.email.val();

        let emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        if (!emailFilter.test(u)) {              //test email for illegal
            throw "Please enter a valid email address.";
        }

    }
}

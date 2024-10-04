class LoginForm extends Component {
    constructor() {
        super();
        this.setClass("FORM");
        this.form = null;
    }

    initialize() {

        super.initialize();

        try {
            this.form = this.getElement();

            if (!(typeof hex_hmac_md5 === 'function')) throw "Required hashing function not defined";

            if (!(this.form instanceof HTMLFormElement)) {
                throw "Component element is not a HTMLFormElement";
            }

            if (!this.form.querySelector("INPUT[name='email']")) throw "Required field 'email' not found";
            if (!this.form.querySelector("INPUT[name='password']")) throw "Required field 'password' not found";
            if (!this.form.querySelector("INPUT[type='hidden'][name='rand']")) throw "Required field 'rand' not found";
            if (!this.form.querySelector("INPUT[type='hidden'][name='pass']")) throw "Required field 'pass' not found";

            const instance = this;
            this.form.addEventListener("submit", (event) => {
                instance.onSubmit(event);
            });



        } catch (exx) {
            alert("Unable to attach with requested Form: " + exx + " " + this.selector());
        }

    }

    async onSubmit(event) {

        this.form.email.value = trim(this.form.email.value);
        this.form.password.value = trim(this.form.password.value);

        try {

            await this.processEmail();
            await this.processPassword();
            return;

        } catch (exx) {
            showAlert(exx);
        }

        event.preventDefault(); // prevent the default form submission behavior
    }

    async processPassword() {

        let p = this.form.password.value;
        let r = this.form.rand.value;

        if (p.length < 1) throw "Input your password to continue.";
        if (p.length < 6) throw "Minimum password of 6 symbols required.";

        this.form.pass.value = hex_hmac_md5(hex_md5(p), r);

        this.form.rand.value = "";
        this.form.password.value = "";

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

    async processEmail() {
        const u = this.form.email.value;
        if (u.length < 1) {
            throw "Input your email";
        }
        const emailFilter = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        if (!emailFilter.test(u)) {              //test email for illegal
            throw "Please enter a valid email address.";
        }

    }
}

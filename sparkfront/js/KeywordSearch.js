class KeywordSearch extends Component {
    constructor() {
        super();
        this.class = ".KeywordSearch";
    }

    initialize() {
        super.initialize();

        if (this.element instanceof HTMLFormElement) {
            this.element.addEventListener('submit', (event) => {
                return this.onSubmit(event);
            });
        }
    }

    submitURL(event) {
        event.preventDefault();

        let form = event.target;
        let url = new URL(form.action || window.location.href);
        let formData = new FormData(form);

        formData.forEach((value, key) => {
            url.searchParams.set(key, value);
        });

        window.location.href = url.href;
    }
    onSubmit(event) {

        const submitter = event.submitter;

        try {
            if (!submitter) throw "";

            const buttonName = submitter.name;
            const buttonValue = submitter.value;

            //const form = document.forms.KeywordSearchForm;
            const query = this.element.keyword.value;
            const minChars = parseInt(this.element.keyword.getAttribute("min-chars"));

            if (submitter.value === "search" && query.length < minChars) throw "Input search term";
            if (submitter.value === "clear" && query.length < 1) throw "";

        }
        catch (e) {
            event.preventDefault();
            if (e) showAlert(e);
            return false;
        }
    }
}
onPageLoad(function() {

    //silence the exception some pages prefer not to render this component
    try {
        let ks = new KeywordSearch();
        ks.initialize();
    }
    catch (e) {}

})
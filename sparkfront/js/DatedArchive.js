class DatedArchive extends Component {
    constructor() {
        super();
        this.class = ".DatedArchive";
    }
    initialize() {
        super.initialize();
        this.element.querySelectorAll(".year .label").forEach(
            elm => elm.addEventListener("click", ()=>this.toggleYear(elm))
        );
    }

    toggleYear(elm) {
        this.element.querySelectorAll(".year").forEach(sel => sel.classList.remove("open"));
        elm.parentElement.classList.add("open")

    }
}
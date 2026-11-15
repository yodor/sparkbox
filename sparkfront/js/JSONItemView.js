function loadMoreResults(button_source)
{
    let itemView = button_source.closest(".JSONItemView");
    let viewPort = itemView.querySelector(".viewport");

    //currentPage = index
    //pagesTotal = count
    let currentPage = parseInt(itemView.getAttribute("page")) ;
    let pagesTotal = parseInt(itemView.getAttribute("pagesTotal"));

    if (isNaN(currentPage)) currentPage = 0;

    if (currentPage < pagesTotal) {
        currentPage++;

        let request = new JSONRequest();
        request.setResponder("ItemViewResponder");
        request.setFunction("renderItems");
        //the page parameter counts from 1, currentPage is the index from 0
        request.setParameter("page", "" + (currentPage + 1));

        request.onSuccess = function (result) {

            document.templateFactory.appendContent(viewPort, result.response.html);

            itemView.setAttribute("page", currentPage);

            button_source.removeAttribute("working");

            let url = new URL(document.location.href);
            url.searchParams.set("page", currentPage + 1);
            window.history.pushState({}, "", url.href);

        };
        button_source.setAttribute("working", "");

        request.start();
    }
    if (currentPage === (pagesTotal-1)) {
        button_source.remove();
    }


}
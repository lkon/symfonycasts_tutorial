// $(document).ready(function () {
//     var $locationSelect = $('.js-article-form-location');
//     var $specificLocationTarget = $('.js-specific-location-target');
//     $locationSelect.on('change', function (e) {
//         console.log($locationSelect.data('specific-location-url'), $locationSelect.val());
//         $.ajax({
//             url: $locationSelect.data('specific-location-url'),
//             data: {
//                 location: $locationSelect.val()
//             },
//             success: function (html) {
//                 if (!html) {
//                     $specificLocationTarget.find('select').remove();
//                     $specificLocationTarget.addClass('d-none');
//                     return;
//                 }
//                 // Replace the current field and show
//                 $specificLocationTarget
//                     .html(html)
//                     .removeClass('d-none');
//             }
//         });
//     });
// });

class ReferenceList {
    constructor($element) {
        this.$element = $element;
        this.references = [];
        this.sortable = Sortable.create(this.$element[0], {
            handle: '.drag-handle',
            animation: 150,
            onEnd: () => {
                $.ajax({
                    url: this.$element.data('url')+'/reorder',
                    method: 'POST',
                    data: JSON.stringify(this.sortable.toArray())
                });
            }
        });

        // this.render();

        $.ajax({
            url: this.$element.data('url')
        }).then(data => {

            console.log(data)
            this.references = data;
            this.render();

            this.$element.on('click', '.js-reference-delete', (event) => {
                this.handleReferenceDelete(event);
            });
            this.$element.on('blur', '.js-edit-filename', (event) => {
                this.handleReferenceEditFilename(event);
            });
        });
    }

    render() {
        const itemsHtml = this.references.map(reference => {
            return `
<li class="list-group-item d-flex justify-content-between align-items-center" data-id="${reference.id}">
    <span class="drag-handle fa fa-reorder"></span>
    <input type="text" value="${reference.originalFilename}" class="form-control js-edit-filename" style="width: auto;">
<!--    // ${reference.originalFilename}-->
    <span>
        <a href="/admin/article/references/${reference.id}/download">
            <span class="fa fa-download"></span>
        </a>
    </span>
    <span>
        <button class="js-reference-delete btn btn-link"><span class="fa fa-trash"></span></button>
    </span>
</li>
`;
        });
        this.$element.html(itemsHtml.join(''));
    }

    addReference(reference) {
        this.references.push(reference);
        this.render();
    }

    handleReferenceDelete(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        $li.addClass('disabled');

        $.ajax({
            url: '/admin/article/references/' + id,
            method: 'DELETE'
        }).then(() => {
            this.references = this.references.filter(reference => {
                return reference.id !== id;
            });
            this.render();
        });
    }

    handleReferenceEditFilename(event) {
        const $li = $(event.currentTarget).closest('.list-group-item');
        const id = $li.data('id');
        const reference = this.references.find(reference => {
            return reference.id === id;
        });
        reference.originalFilename = $(event.currentTarget).val();
        $.ajax({
            url: '/admin/article/references/' + id,
            method: 'PUT',
            data: JSON.stringify(reference)
        }).catch(function (jqXHR, textStatus, errorThrown) {
            const {responseJSON} = jqXHR;
            console.log(responseJSON);
        });
    }
}

/**
 * @param {ReferenceList} referenceList
 */
function initializeDropzone(referenceList) {

    Dropzone.autoDiscover = false;

    const formElement = document.querySelector('.js-reference-dropzone');
    if (!formElement) {
        return;
    }
    const dropdown = new Dropzone(formElement, {
        paramName: 'reference',
        init: function () {
            this.on('error', function (file, data) {
                if (data.detail) {
                    this.emit('error', file, data.detail);
                }
            });
            this.on('success', function (file, data) {
                referenceList.addReference(data);
            });
        }
    });
}

var referenceList = new ReferenceList($('.js-reference-list'));
initializeDropzone(referenceList);

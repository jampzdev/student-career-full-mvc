psslai



    .service('loginModal', function ($uibModal, $window) {

          function assignCurrentUser (user) {
             $window.localStorage.currentUser = JSON.stringify(user);
             window.location.reload(true);
            return user;
          }

          return function() {
            var instance = $uibModal.open({
              templateUrl: baseUrl+'/index/login',
              controller: 'LoginModalCtrl',
              controllerAs: 'LoginModalCtrl'
            })

            return instance.result.then(assignCurrentUser);
          };
    })
    .service('createModal', ["$uibModal", "$log", function ($uibModal, $log) {
        var md = {};

        md.modalInstances = function (animation, size, backdrop, keyboard, content, templateUrl, scope) {
            var modalInstance = $uibModal.open({
                animation: animation,
                templateUrl: templateUrl,
                controller: 'ModalInstanceCtrl',
                size: size,
                scope: scope,
                backdrop: backdrop,
                keyboard: keyboard,
                resolve: {
                    content: function () {
                        return content;
                    }
                }
            });
        }

        return md;
    }])

    // =========================================================================
    // Malihu Scroll - Custom Scroll bars
    // =========================================================================
    .service('scrollService', function() {
        var ss = {};
        ss.malihuScroll = function scrollBar(selector, theme, mousewheelaxis) {
            $(selector).mCustomScrollbar({
                theme: theme,
                scrollInertia: 100,
                axis:'yx',
                mouseWheel: {
                    enable: true,
                    axis: mousewheelaxis,
                    preventDefault: true
                }
            });
        }

        return ss;
    })

   //============================================
    // Swal prompt
    //============================================
    .service('prompt', function () {
        this.success = function (message) {
            swal('Success', message, 'success');
        }
        this.warning = function (message) {
            swal('Oops', message, 'warning');
        }
        this.error = function (message) {
            swal('Error', message, 'error');
        }
    })



    //==============================================
    // BOOTSTRAP GROWL
    //==============================================

    .service('growlService', function(){
        var gs = {};
        gs.growl = function(message, type) {
            if(document.getElementsByClassName('growl-animated').length != 0){
                document.getElementsByClassName('growl-animated')[0].remove();
            }
            var title = type != 'success' ? 'Oops...' : 'Everything is okay.';
            $.growl({
                message : message
            },{
                z_index: 1080,
                type: type,
                allow_dismiss: true,
                mouse_over: "pause",
                label: 'Cancel',
                className: 'btn-xs btn-inverse',
                placement: {
                    from: 'top',
                    align: 'center'
                },
                delay: 2500,
                spacing: 10,
                animate: {
                    enter: 'animated bounceIn',
                    exit: 'animated bounceOut'
                },
                offset: {
                    x: 20,
                    y: 85
                }
            });
        }
        return gs;
    })
    .service('modalService',function($uibModal,$log){
        var md = {};
            md.modalInstances = function(animation, size, backdrop, keyboard,templateUrl, scope){
                var modalInstance = $uibModal.open({
                    templateUrl: templateUrl,
                    controller: 'ModalInstanceCtrl',
                    size: size,
                    scope:scope,
                    backdrop: backdrop,
                    keyboard: keyboard,
                    resolve: {
                        content: function () {
                            return content;
                        }
                    }

                });
            }
        return md;
    })
    .service('validate',function(){
        var em  =   {};
            em.email    =   function(email){
                var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            }
            em.website  =   function(webUrl){
                var url = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/;
                return url.test(webUrl);
            }
            em.password =   function(pass){
                var pd = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/;
                return pd.test(pass);
            }
          return em;
    })

    .service('helper', function () {
        var help = {};
        help.toHtml = function (str) {
            return str
                .replace(/&amp;/g, "&")
                .replace(/&#38;/g, "&")
                .replace(/&lt;/g, "<")
                .replace(/&gt;/g, ">")
                .replace(/&quot;/g, '"')
                .replace(/&#34;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&#39;/g, "'");

        };
        help.escapeHtml = function (str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        }
        help.nl2br = function (str, is_xhtml) {
            var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        }
        help.makeLink = function (input) {
            var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            var extendText = help.makeIcon(input);
            return extendText.replace(exp, "<a href='$1'>$1</a>");
        }
        help.isNumeric = function (number) {
            return !isNaN(parseFloat(number)) && isFinite(number);
        }
        help.website = function (url) {
            if (!validate.website(url)) {
                return false;
            }
            return true;
        }
        help.validate = function () {
            angular.element('input,textarea,select').on('blur change', function (ev) {
                var type = ev.target.type;
                var isRequired = ev.target.attributes['required'];
                if (typeof isRequired != 'undefined') {
                    var parent = ev.target.parentNode;
                    if (parent.parentNode.children.length > 1) {
                        if (parent.parentNode.children[1] != 'undefined' && parent.parentNode.children[1].classList.contains('error-label-message')) {
                            if (parent.parentNode.children[1].nodeName == 'LABEL') {
                                parent.parentNode.removeChild(parent.parentNode.children[1]);
                            }
                        }
                    }
                    if (ev.target.value.length == 0) {
                        parent.classList.add('has-error');
                        if (type === 'select-one') {
                            if (ev.target.classList.contains('selectized')) {
                                ev.target.nextSibling.firstChild.classList.add('has-selectize-error');
                            }
                        }
                        var append = parent.parentNode.appendChild(document.createElement('label'));
                        append.innerHTML = 'This field is required.';
                        append.style.color = '#f44336';
                        append.classList = 'error-label-message';

                    }
                    else {
                        if (type == 'email') {
                            if (!validate.email(ev.target.value)) {
                                parent.classList.add('has-error');
                                var append = parent.parentNode.appendChild(document.createElement('label'));
                                append.innerHTML = 'Please enter a valid email.';
                                append.style.color = '#f44336';
                                append.classList = 'error-label-message';
                            }
                            else {
                                parent.classList.remove('has-error');
                            }
                        }
                        else if (type == 'select-one') {
                            if (ev.target.classList.contains('selectized')) {
                                ev.target.nextSibling.firstChild.classList.remove('has-selectize-error');
                            }
                        }
                        else {
                            parent.classList.remove('has-error');
                        }
                    }
                }
            });
        }
        help.removeValidate = function (el) {
            angular.element(el).find('.has-error').removeClass('has-error');
            angular.element(el).find('.error-label-message').remove();
            angular.element(el).find('.has-selectize-error').removeClass('has-selectize-error');
        }
        help.getParent = function (parentNode, childeNode, type) {
            var obj = childeNode.parentNode;
            if (type == 'tag' || type == null) {
                while (obj.tagName != parentNode) {
                    obj = obj.parentNode;
                }
            }
            else if (type == 'id') {
                while (obj.id != parentNode) {
                    obj = obj.parentNode;
                }
            }
            else if (type == 'class') {
                while (obj.classList.contains(parentNode) === false) {
                    obj = obj.parentNode;
                }
            }
            return obj;
        }
        help.in_array = function (needle, haystack) {
            //needle is the one to be search
            //haystack is the array
            for (var i in haystack) {
                if (haystack[i] == needle) return true;
            }
            return false;
        }
        help.validExtension = function (ext) {
            var listExt = array = [
                /* Vid Allowed Extensions */
                'wmv',
                'avi',
                'mp4',
                'mpg',
                'mov',
                'flv',
                '3gp',
                /* Image Allowed Extensions */
                'tif',
                'jpg',
                'jpeg',
                'png',
                'gif',
                /* Documents Files */
                'accdb',
                'accdt',
                'doc',
                'docm',
                'docx',
                'dot',
                'dotm',
                'dotx',
                'mdb',
                'mpd',
                'mpp',
                'mpt',
                'oft',
                'one',
                'onepkg',
                'pot',
                'potx',
                'pps',
                'ppsx',
                'ppt',
                'pptm',
                'pptx',
                'pst',
                'pub',
                'snp',
                'thmx',
                'vsd',
                'vsdx',
                'xls',
                'xlsm',
                'xlsx',
                /* Extended Documents*/
                'ps',
                'pages',
                'pdf',
                'csv',
                'txt',
                /* Compress and belong to this will belong to documents files*/
                'jar',
                'zip',
                'tar',
                'rar',
                'gz',
                'gzip',
                'tgz'
            ];
            if (help.in_array(ext, listExt)) { return true; }
            else { return false; }
        }
        help.IsExcelFile = function (file) {
            return (file.indexOf(".xls") != -1 || file.indexOf(".xlsx") != -1) ? true : false;
        }

        help.IsEmptyString = function (value) {
            if (value == undefined || value == null || value.trim() == "") {
                return true;
            }
            return false;
        }
        help.IsEmptyInt = function (value) {
            if (value == undefined || value == null || value == 0) {
                return true;
            }
            return false;
        }
        help.deleteItemInArray = function (arrayobj, obj) {
            var index = arrayobj.indexOf(obj);
            arrayobj.splice(index, 1);
        }
        help.deleteByAttr = function (arr, attr, value) {
            // console.log(arr);
            var i = arr.length;
            while (i--) {
                if (arr[i] && arr[i].hasOwnProperty(attr) && (arguments.length > 2 && arr[i][attr] === value)) {
                    arr.splice(i, 1);
                }
            }
            return arr;
        }

        return help;
    })

    .service('processExcelFile', function () {
        var sheetName = '';
        this.toJsonObject = function (workbook) {
            var result = {};
            sheetName = workbook.SheetNames[0];
            workbook.SheetNames.forEach(function (sheetName) {
                var roa = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1});
                if (roa.length) result[sheetName] = roa;
            });
            return JSON.stringify(result, 2, 2);
        };
        this.sanitizedJsonData = function (data) {
            var len = Object.keys(data[sheetName]).length;
            summarize_logs = [];
            for (var i = 0; i < len; i++) {
                summarize_logs.push(data[sheetName][i]);
            }
            return summarize_logs;
        }
        this.getHeaders = function (workbook) {
            var result = {};
            sheetName = workbook.SheetNames[0];
            workbook.SheetNames.forEach(function (sheetName) {
                var roa = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: 1 });
                if (roa.length) result[sheetName] = roa;
            });
            var header = result;
            return JSON.stringify(header[sheetName][0]);
        }
        this.exportExcel = function (id) {
            var fileName = id + ".xlsx";
            saveAs(new Blob([angular.element('#' + id)[0].outerHTML], { type: "application/octet-stream" }), fileName);
        }
    })

    .service('paginate',function(){
        var tr  =   {};
            tr.pager    =   function(data, pager, functionToCall){
                var pageHtml    =   '';
                var pmin        =   0;
                var pmax        =   0;
                var adjacents   =   1;
                pageHtml += '<li><span>Page '+data.currentPage+' of '+data.total+'</span></li>';
                if(data.currentPage == '1'){
                    pageHtml+='<li class="disabled"><a>&laquo;</a></li>';
                }
                else{
                    pageHtml+='<li><a style="cursor: pointer;" data-ng-click="'+functionToCall+'('+data.prevousPage+',$event)" title="Previous Page">&laquo;</a></li>';
                }
                if(pager > (adjacents + 1)) {
                    pageHtml+='<li><a style="cursor: pointer;" data-ng-click="'+functionToCall+'(1,$event)">1</a></li>';
                }
                if(pager>(adjacents)){
                    pageHtml+='<li class="hidden-xs"><span>...</span></li>';
                }
                pmin = (pager > adjacents) ? (pager-adjacents) : 1;
                pmax = (pager < (data.total - adjacents)) ? (pager + adjacents) : data.total
                for(var i = pmin; i <= pmax; i++) {
                    if(i == data.currentPage) {
                        pageHtml +='<li class="active"><a style="cursor: pointer;"><span>'+i+'</span></a></li>';
                    }
                    else if(i==1) {
                        pageHtml+='<li class="hidden-xs"><a style="cursor: pointer;" data-ng-click="'+functionToCall+'('+i+',$event)">'+i+'</a></li>';
                    }
                    else{
                        pageHtml+='<li class="hidden-xs"><a style="cursor: pointer;" data-ng-click="'+functionToCall+'('+i+',$event)">'+i+'</a></li>';
                    }
                }
                if(pager < ( (data.total - adjacents) - 1)) {
                    pageHtml+='<li class="hidden-xs"><span>...</span></li>';
                }
                if(pager < (data.total-adjacents)) {
                    pageHtml+='<li><a style="cursor: pointer;" data-ng-click="'+functionToCall+'('+data.total+',$event)">'+data.total+'</a></li>';
                }
                if(data.currentPage == data.noOfPage){
                    pageHtml+='<li class="disabled"><a>&raquo;</a></li>';
                }
                else{
                    pageHtml+='<li><a style="cursor: pointer;" data-ng-click="'+functionToCall+'('+data.nextPage+',$event)" title="Next Page">&raquo;</a></li>';
                }
                return pageHtml;
            }
        return tr;
    })
    .service("httpRequest",['$http', '$q',function( $http, $q ) {
        var request =  {};
        request.search  =   function(url,params, method){
            var deferredAbort = $q.defer();
            // Initiate the AJAX request.
            var request = $http({
                method: method == null ? "GET" : method,
                url: url,
                params:params,
                timeout: deferredAbort.promise,
                ignoreLoadingBar: true
            });
            var promise = request.then(
                function( response ) {
                    return( response.data );
                },
                function( response ) {
                    return( $q.reject( "Something went wrong" ) );
                }
            );
            promise.abort = function() {
                deferredAbort.resolve();
            };
            promise.finally(
                function() {
                    promise.abort = angular.noop;
                    deferredAbort = request = promise = null;
                }
            );
            return( promise );
        }
        return request;
    }])
    .service('settingCode',['growlService','$http','helper','paginate','$compile','httpRequest',function(growlService,$http,helper,paginate,$compile, httpRequest){
        var method = {}
        var xhr;
        method.get      =   function(objparams){
            page   =  objparams.newPage;
            $http({
                method: 'GET',
                url: baseUrl + '/settings/get/constantCode',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                params:{
                    'page'   : objparams.newPage,
                    'row'    : row,
                    'sort'   : objparams.type,
                    'sortVal': objparams.sortVal,
                    'type'   : objparams.codeType
                }
            })
            .then(
                function successCallback(response){
                    var data  =   response.data;
                    if(data.statusCode === 200){
                        if(data.devMessage != ''){
                            objparams.scope.resultData   =   data.devMessage.rows;
                            var stringPage      =   paginate.pager(data.devMessage.pageInfo,page,'getData');
                            var stringCompiled  =   $compile(stringPage)(objparams.scope);
                            $('#pagination').html(stringCompiled);
                        }
                        else{
                            objparams.scope.resultData   =   [];
                            $('#pagination').html('');
                        }
                    }
                    else{
                        objparams.scope.resultData   =   [];
                        $('#pagination').html('');
                        growlService.growl(data.devMessage,'warning');
                    }
                },
                function errorCallback(response){
                    growlService.growl('Something went wrong. Please reload the page and try again. additional: <span class="error">'+response.statusText+'</span>','warning');
                }
            );
        }
        method.add      =   function(objparams){
            if(helper.IsEmptyString(objparams.code)){
                growlService.growl('Code is required.','warning')
                return;
            }
            else if(!(/^[a-z0-9 .\-]+$/i).test(objparams.code)){
                growlService.growl('Code is invalid.','warning');
                return;
            }
            else if(helper.IsEmptyString(objparams.location)){
                if(objparams.typeCode == 'coc' || objparams.typeCode == 'pc'){
                    growlService.growl('Description is required.','warning');
                }
                else if(objparams.typeCode == 'pod'){
                    growlService.growl('Name is required.','warning');
                }
                else{
                    growlService.growl('Location is required.','warning');
                }

                return;
            }
            else if(helper.IsEmptyString(objparams.typeCode)){
                growlService.growl('Type is required.','warning')
                return;
            }
            $http({
                url: baseUrl + '/settings/set/constantCode',
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param({
                    'code'     : objparams.code,
                    'location' : objparams.location,
                    'type'     : objparams.typeCode
                })
            })
            .then(
                function successCallback(response){
                    var data  =   response.data;
                    if(data.statusCode == 200){
                        growlService.growl('New code has been created','success');
                        objparams.scope.getData(1);
                        objparams.scope.cancel();
                    }
                    else{
                        growlService.growl(data.devMessage,'warning');
                    }
                },
                function errorCallback(response){
                    growlService.growl('Something went wrong. Please reload the page and try again. additional: <span class="error">'+response.statusText+'</span>','warning');
                }
            );
        }
        method.getInfo  =   function(objparams){
            // id,crypt,type,scope,row
            if(objparams.key !== null && objparams.crypt !== null){
                $http({
                    method: 'GET',
                    url: baseUrl + '/settings/getInfo/constantCode',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    params:{
                        'key'   : objparams.key,
                        'crypt' : objparams.crypt,
                        'type'  : objparams.typeCode
                    }
                })
                .then(
                    function successCallback(response){
                        document.body.style.ponterEvents = 'auto';
                        var data    =   response.data;
                        if(data.statusCode === 200){
                            if (data.devMessage != null){
                                var info  = data.devMessage;
                                objparams.scope.codeItems = {
                                    id    : objparams.key,
                                    crypt : objparams.crypt
                                }
                                objparams.scope.openModal();
                                if(objparams.typeCode == "bl"){
                                    objparams.scope.form.blcode   = info.code;
                                    objparams.scope.form.location = info.location;
                                    setTimeout(function(){
                                        $('#location').focus();
                                    },100);
                                }else if(objparams.typeCode == "pd" || objparams.typeCode == "cr"){
                                    objparams.scope.form.pdcode   = info.code
                                    objparams.scope.form.location = info.location
                                    setTimeout(function(){
                                        $('#location').focus();
                                    },100);
                                }
                                else if(objparams.typeCode == 'coc' || objparams.typeCode == 'pod' || objparams.typeCode == 'pc'){
                                    console.log(info.location);
                                    setTimeout(function(){
                                        $('#code').val(info.code),
                                        $('#location').val(info.location).focus().blur();
                                    },100)
                                }

                            }
                        }
                        else{
                            growlService.growl(data.devMessage,'warning');
                        }
                    },
                    function errorCallback(response){
                        document.body.style.ponterEvents = 'auto';
                        growlService.growl('Something went wrong. Please reload the page and try again. additional: <span class="error">'+response.statusText+'</span>','warning');
                    }
                );
            }
            else{
                growlService.growl('Sorry, Something went wrong. Please reload the page and try again','warning');
            }
        }
        method.update   =   function(objparams){
            var key     = objparams.scope.codeItems.id,
                crypt   = objparams.scope.codeItems.crypt;
            if(helper.IsEmptyString(objparams.code)){
                growlService.growl('Code is required.','warning')
                return
            }
            else if(!(/^[a-z0-9 .\-]+$/i).test(objparams.code)){
                growlService.growl('Code is invalid.','warning');
                return;
            }
            else if(helper.IsEmptyString(objparams.location)){
                if(objparams.typeCode == 'coc' || objparams.typeCode == 'pc'){
                    growlService.growl('Description is required.','warning');
                }
                else if(objparams.typeCode == 'pod'){
                    growlService.growl('Name is required.','warning');
                }
                else{
                    growlService.growl('Location is required.','warning');
                }
                return;
            }
            else if(helper.IsEmptyString(objparams.typeCode)){
                if(objparams.typeCode == 'coc' || objparams.typeCode == 'pc'){
                    growlService.growl('Description is required.','warning');
                }
                else if(objparams.typeCode == 'pod'){
                    growlService.growl('Name is required.','warning');
                }
                else{
                    growlService.growl('Location is required.','warning');
                }
                return
            }
            $http({
                url: baseUrl + '/settings/update/constantCode',
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param({
                    'code'     : objparams.code,
                    'location' : objparams.location,
                    'type'     : objparams.typeCode,
                    'key'      : key,
                    'crypt'    : crypt
                })
            })
            .then(
                function successCallback(response){
                    var data    =   response.data;
                    if(data.statusCode == 200){
                        objparams.scope.details.code     = objparams.code;
                        objparams.scope.details.location = objparams.location;
                        objparams.scope.codeItems   =   {};
                        growlService.growl('Code has been updated','success');
                        objparams.scope.cancel();
                    }
                    else{
                        growlService.growl(data.devMessage,'warning');
                    }
                },
                function errorCallback(response){
                    growlService.growl('Something went wrong. Please reload the page and try again. additional: <span class="error">'+response.statusText+'</span>','warning');
                }
            );
        }
        method.delete   =   function(id, crypt, ev, scope,type){
            if(id !== null && crypt !== null){
                swal({
                    title: "Are you sure?",
                    text: "You won't be able to recover this record.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it",
                    closeOnConfirm: true
                },
                function(){
                    $http({
                        url:baseUrl+'/settings/delete/constantCode',
                        method:"POST",
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param({
                            'key'  : id,
                            'crypt': crypt,
                            'type' : type
                        })
                    })
                    .then(function successCallback(response){
                        var data    =   response.data;
                        if(data.statusCode == 200){

                            helper.getParent('TR', ev.target).remove();
                            if($('.setting-table').find('tbody > tr').length <= 5){ scope.getData(1); }
                            if(type == 'pod'){ growlService.growl("Place of Destination has been deleted successfully.", "success"); }
                            else if(type == 'coc'){ growlService.growl("Custom Office Code has been deleted successfully.", "success"); }
                            else if(type == 'pc'){ growlService.growl("Packaging Code has been deleted successfully.", "success"); }
                            else if(type == 'bl'){ growlService.growl("B/L Nature Code has been deleted successfully.", "success"); }
                            else if(type == 'pd'){ growlService.growl("Place of Departure has been deleted successfully.", "success"); }
                            else{
                                growlService.growl("Record has been deleted successfully.", "success");
                            }

                        }
                        else{
                            swal("Error", data.devMessage, "error");
                        }
                    },
                    function errorCallback(response){
                        growlService.growl(typeof response.statusText != 'undefined' && response.statusText != '' ? response.statusText : 'Something went wrong. Please reload the page and try again.','warning');
                    });
                });
            }
            else{
                growlService.growl('Sorry, Something went wrong. Please reload the page and try again','warning');
            }
        }
        method.search   =   function(objparams, scope){
            page = objparams.page;
            if(typeof xhr == 'object'){ xhr.abort();}
            xhr = httpRequest.search(baseUrl + '/settings/get/search',objparams, 'GET');
            xhr.then(function(data){
                if(data.statusCode === 200){
                    if(data.devMessage !== ''){
                        scope.resultData    =   data.devMessage.rows;
                        var stringPage      =   paginate.pager(data.devMessage.pageInfo,page,'search');
                        var stringCompiled  =   $compile(stringPage)(scope);
                        $('#pagination').html(stringCompiled);
                    }
                    else{
                        growlService.growl("No records found.",'warning');
                    }
                }
                else{
                    growlService.growl(data.devMessage,'warning');
                }
            });
        }
        return method;
    }])


    //==========================================================================
        // Check input if null, empty or 0 supports text, mask and select input
        //==========================================================================
    .service('inputChecker', function(){
        this.textInput = function(fields){
            if(fields != undefined){
                var i   =   fields.length;
                var res =   false;
                while(i--){
                    var thisInput = angular.element("#"+fields[i]).val();
                    if(thisInput.trim().length == 0){
                        angular.element("#"+fields[i]+"Container").addClass('has-error');
                        res = true;
                    }
                    else{
                        angular.element("#"+fields[i]+"Container").removeClass('has-error');
                    }
                }
                return res;
            }
        }
        this.selectInput = function(fields){
            if(fields != undefined){
                var i =   fields.length;
                var res =   false;
                while(i--){
                    var thisInput = angular.element("#"+fields[i]).val();

                    if(thisInput == "" || thisInput == null || thisInput == "? string:undefined ?" || thisInput == "?" || thisInput == "? string:null ?"){
                        angular.element("#"+fields[i]+"Container").addClass('has-error');
                        res = true;
                    }
                    else{
                        angular.element("#"+fields[i]+"Container").removeClass('has-error');
                    }
                }
                return res;
            }
        }
        this.maskInput = function(fields){
            if(fields != undefined){
                var i =   fields.length;
                var res =   false;
                while(i--){
                    if(!$("#"+fields[i]).inputmask("isComplete") || $("#"+fields[i]).val() == ""){
                        angular.element("#"+fields[i]+"Container").addClass('has-error');
                        res = true;
                    }
                    else{
                        angular.element("#"+fields[i]+"Container").removeClass('has-error');
                    }
                }
                return res;
            }
        }
        this.emailInput = function(fields){
            if(fields != undefined){
                var i           =   fields.length;
                var res         =   false;
                while(i--){
                    var thisInput = angular.element("#"+fields[i]).val();

                    //email validation condition
                    var checkmail =   testMail(thisInput);
                    if(thisInput.trim().length == 0 || checkmail == false){
                        angular.element("#"+fields[i]+"Container").addClass('has-error');
                        res = true;
                    }
                    else{
                        angular.element("#"+fields[i]+"Container").removeClass('has-error');
                    }
                }
                return res;
            }
        }
        function testMail(email){
            var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }
    })

    //============================================
    // Revalidate inputs
    //============================================
    .service('validator', function () {
        this.check = function (array) {
            for (var i in array) {
                if (angular.element("#" + array[i] + "Container").hasClass('has-error')) {
                    angular.element("#" + array[i]).focus();
                    return;
                }
            }
        }
        this.date_format = function (dateString) {
            // First check for the pattern
            if (!/^\d{4}\-\d{1,2}\-\d{1,2}$/.test(dateString)) {
                return false;
            }

            // Parse the date parts to integers
            var parts = dateString.split("-");
            var day = parseInt(parts[2], 10);
            var month = parseInt(parts[1], 10);
            var year = parseInt(parts[0], 10);

            // Check the ranges of month and year
            if (year < 1000 || year > 3000 || month == 0 || month > 12) {
                return false;
            }

            var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

            // Adjust for leap years
            if (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)) {
                monthLength[1] = 29;
            }

            // Check the range of the day
            console.log(day > 0 && day <= monthLength[month - 1])
            return day > 0 && day <= monthLength[month - 1];
        }

    })

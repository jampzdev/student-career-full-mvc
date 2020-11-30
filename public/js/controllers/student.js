psslai

.controller('StudentCtrl', ['createModal', 'helper', '$timeout', '$scope', '$rootScope', '$window', '$state', '$http', 'growlService', 'processExcelFile', 'NgTableParams', 'modalService',
function (createModal, helper, $timeout, $scope, $rootScope, $window, $state, $http, growlService, processExcelFile, NgTableParams, modalService) {

    $scope.openFileUpload = function(){
        $('#info-select-file').val('');
        angular.element("#info-select-file").trigger('click');
    }
    angular.element("#info-select-file").change(function() {
      $scope.importStudentCsv();    // $scope.processCsv();
    });
    $scope.importStudentCsv = function(){
      var form_data   = new FormData();
      var files       = document.getElementById("info-select-file").files;
      form_data.append('file', files[0]);
      $http.post(baseUrl+'/student/insertFromCsv', form_data,
      {
           transformRequest: angular.identity,
           headers: {
              'Content-Type': undefined,
              'Process-Data': false
          }
      }).success(function(response){
          var res = response.message;
          if(res=== "success"){
          } else {

          }
      })
    }

    $scope.getStudentList = function () {
      $scope.studentTable  = new NgTableParams({
           sorting : {
               career    :   'asc'   // Column name and sort type
           },
               page    :   1,          // Page number
               count   :   10,         // Record count per page
        },{
           total   :   0,              //  Initial total record count
           getData : function($defer, params) {
               var page   =    params.page();
               var count  =    params.count();

               $http({
                   method  : 'POST',
                   url: baseUrl + '/student/getStudentList',
                   headers: {
                       'Content-Type'  : 'application/x-www-form-urlencoded; charset=UTF-8'
                   },
                   data    :  JSON.stringify({
                       page         : page,
                       count        : count,
                   })
               })
               .then(function successCallback(response) {
                   $scope.dataList = response.data.devMessage;
                   $scope.studentTable.total(response.data.totalItems);
                   $defer.resolve($scope.dataList);
                   $scope.dataList.total = response.data.totalItems;
                   $scope.dataList.page_details = (params.count() * params.page() - params.count() + 1) + ' TO ' + (params.count() * params.page() - (params.count() - $scope.dataList.length)) + ' OF ' + response.data.totalItems + ' ENTRIES';
                   $('html, body').animate({
                       scrollTop: 0
                   }, 800);
                   $(window).on('mousewheel', function() {
                       $('html, body').stop();
                   });
               },
               function errorCallback(response){

               });
           }
       });
    }

}])

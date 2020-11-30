psslai

.controller('CareerCtrl', ['createModal', 'helper', '$timeout', '$scope', '$rootScope', '$window', '$state', '$http', 'growlService', 'processExcelFile', 'NgTableParams', 'modalService',
function (createModal, helper, $timeout, $scope, $rootScope, $window, $state, $http, growlService, processExcelFile, NgTableParams, modalService) {


  $scope.careerList = [];
  $scope.add  = [];
  $scope.careerList = []
  $scope.form =[];
  $scope.onEditableVerify = function (action) {
    if(action == 'add'){
      var obj = {
        "career_name" : $scope.add.careerName,
        "position"    : $scope.add.position,
        "skills"      : $scope.add.skills
      }
      $scope.careerList.push(obj);
      $scope.add = [];
      console.log($scope.careerList);
    }

  }
  $scope.onEditableDelete = function (ev){
    var thisRow = angular.element(ev.currentTarget);
    var thisId = thisRow.data('id');
    $scope.careerList.splice(thisId, 1);
  }


  $scope.clearFields = function() {
      $scope.form = {};
      $scope.cancel();
  }


  $scope.addupdate = function () {
      $http({
          method: "POST",
          url: baseUrl + "/career/addUpdateList",
          headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          data: {
            career    : $scope.careerList,
            category  : $scope.form.opportunity.category,
            tags      : $scope.form.opportunity.tags
          }
      })
      .then(function successCallback(response) {
          growlService.growl("Category added successfully","success");
      },function errorCallback(response){

      });
  }

  $scope.getCategoryList = function () {
    $scope.careerTable  = new NgTableParams({
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
                 url: baseUrl + '/career/getCategoryList',
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
                 $scope.careerTable.total(response.data.totalItems);
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

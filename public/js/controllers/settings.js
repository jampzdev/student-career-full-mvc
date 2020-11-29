psslai

.controller('StudentCtrl', ['createModal', 'helper','$timeout','$scope','$rootScope','$window','$state', '$http', 'growlService','processExcelFile','NgTableParams','modalService',
function (createModal, helper, $timeout, $scope,$rootScope,$window,$state, $http, growlService, processExcelFile, NgTableParams, modalService) {

    $scope.test = function(){
        alert("dafdsfa");
    }

    $scope.addStudentForm = function(){
        createModal.modalInstances(true, 'm', 'static', false, $scope.modalContent, 'addUserRoles', $scope);
    }
    
}])
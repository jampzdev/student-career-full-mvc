psslai

.controller('CareerCtrl', ['createModal', 'helper', '$timeout', '$scope', '$rootScope', '$window', '$state', '$http', 'growlService', 'processExcelFile', 'NgTableParams', 'modalService',
function (createModal, helper, $timeout, $scope, $rootScope, $window, $state, $http, growlService, processExcelFile, NgTableParams, modalService) {

    $scope.test = function () {
        $scope.getStudentList();
    }

    $scope.getStudentList = function(){
        $http({
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            url: baseUrl + '/career/getStudentList',
            data: JSON.stringify({

            })

        }).then(function successCallback(response) {

        }, function errorCallback(response) {

        });
    }

}])
/*
   Script: js/admin.js
      JavaScript file used for adding functionality to the LIR
      plugin.
      
   About: Plugin
      Library Instruction Recorder

   About: License
      GPLv3


   Library Instruction Recorder - A WordPress Plugin
   Copyright (C) 2013 Georgia State University Library

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


/*
   Function: jQuery(function($){})
      On document load.
   
   Inputs:
      $  -  Sets the jQuery object to be $ since jQuery is running in no conflict mode.
*/
jQuery(function($) {
   //Initializes DatePicker for add a class.
   if($('#classDate').length) {
      $('#classDate').datepicker({
         dateFormat : 'm/d/yy'
      });
   }
   
   if($('#reportStartDate').length) {
      $('#reportStartDate').datepicker({
         dateFormat : 'm/d/yy'
      });
   }
   
   if($('#reportEndDate').length) {
      $('#reportEndDate').datepicker({
         dateFormat : 'm/d/yy'
      });
   }

   //Stops the delete links on the upcoming classes page from firing.
   $('.removeLink').each(function() {
      $(this).click(function(e) {
         e.preventDefault();
      });
   });

   //Stops the details links on the upcoming classes page from firing.
   $('.detailsLink').each(function() {
      $(this).click(function(e) {
         e.preventDefault();
      });
   });
});


//Sets up $j for jQuery no conflict mode.
var $j = jQuery.noConflict();


/*
   Function: removeClass
      Displays a prompt for the removal of a class.
   
   Inputs:
      url   -  A URL to forward the browser to if the confirm box is true.
   
   Outputs:
      A confirm box.
*/
function removeClass(url) {
   var check = confirm("Are you sure you want to remove this class?");

   if(check) {
      window.location.href = url;
   }
}


/*
   Function: showDetails
      Constructs and shows the details of a class. Uses jQueryUI dialog to handle this.
   
   Inputs:
      id -  The ID of the class to display.
   
   Outputs:
      A jQueryUI dialog box containing class details.
*/
function showDetails(id) {
   var $element = $j('<table></table>').attr({cellspacing: 0, cellpadding: 0});
   
   $j('.'+id+' > td').each(function() {
      if($j(this).attr('name')) {
         if($j(this).attr('name') == 'skip') { return true; }
         field = $j(this).attr('name').replace(/-/g, '/').replace(/_/g, ' ');
      }
      else {
         field = '';
      }
      
      $j($element).append('<tr><td>'+field+'</td><td>'+$j(this).html()+'</td></tr>');
   });
   
   $j($element).find('tr:last').attr('class', 'last');
   $element = $j('<div></div>').attr('id', 'LIR-popup').append($element);
   
   $j($element).dialog({
      title: 'Details',
      width: 360
   });
}

//sldjfslkdfjslkdfjk
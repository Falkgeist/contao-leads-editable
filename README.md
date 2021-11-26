leads
=====

This is a Contao extension that allows you to store and manage form data within Leads. Each
Lead consists of a master form and optional slave forms. The master form defines which fields are 
available. This approach helps you e.g. to implement multilingual forms and store all data in the 
same Lead.

All configuration can be done in the form generator from Contao - please do not forget to activate 
the fields you want to save. Additionally you can set a label for the backend module of your Lead 
and define the listing of the form data using simple tags.

The leads extension additionally offers an export function for each Lead in the backend. You can 
configure it as you wish! Export options for CSV and Excel are available. _However_ you need to 
install either the `phpoffice/phpspreadsheet` or `phpoffice/phpexcel` package for Excel support, 
otherwise the Excel export option will not be available. 

Editable Values
-------------

This fork enables the 'Edit' function for the fields of each lead.
To edit the values, just click the button next to the field in list view of the lead.
For now, the following input fields are tested and supported:

- Text
- Hidden
- Checkbox
- Select (incl. multiple)
- Radio

Simple Tokens
-------------

The listing of the form data in the Contao backend can be configured using simple tokens, e.g.:

    ##created## - ##name## ##firstname##

Note: there is an additional tag available for the creation date: ##created##

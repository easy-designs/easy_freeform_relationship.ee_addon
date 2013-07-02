Easy Freeform Relationship FieldType
====================================

Ever want to relate an ExpressionEngine Channel entry to a Freeform
submission? Now you can.

API
---

After installing this fieldtype, you will need to add a new field to
the Channel you want to associate with a given Freeform form. Choose
the form to associate in the field settings and then set up to three
fields you would like to include in the dropdown for each form 
submission (e.g. Name, Email, Subject).

Then, in your template, use the field’s short name as a tag pair and
go to town. You can reference any built-in Freeform fields and any custom
fields you have created. For example:

	{question_asked}
		<blockquote>
			<p><q>{question}</q></p>
			<p class="citation"><a href="{url}">{name}</a></p>
		</blockquote>
	{/question_asked}

In this example, `question_asked` is the short name given to the 
relationship field of the channel entry and `question`, `url`, and 
`name` are the variables collected by Freeform in the associated 
submission.
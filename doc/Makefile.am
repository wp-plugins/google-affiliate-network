Article_TEXS = gan_manual.tex
Article_FIGS = ganlinkads.png ganaddlinkad.png ganaddlinkbulk.png \
		ganLinksTab.png ganExport.png ganwidget.png ganlinkads.png \
		ganproducts.png ganaddproduct.png ganaddproductbulk.png \
		ganProductsTab.png ganExportProducts.png ganimagewidget.png \
		ganproductwidget.png ganmediabutton.png ganinsertaddialog.png

Article_PDF  = gan_manual.pdf

EXTRA_DIST=$(Article_TEXS) $(Article_FIGS)

noinst_DATA=$(Article_PDF)


$(Article_PDF) : $(Article_TEXS) $(Article_FIGS)
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual

CLEANFILES = *.log *.aux *.toc *.bbl *.blg


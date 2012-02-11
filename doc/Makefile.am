Article_TEXS = gan_manual.tex
Article_FIGS = gandatabase.png ganmanualadd.png bulkadd.png ganLinksTab.png \
		ganExport.png ganwidget.png ganimagewidget.png \
		ganmediabutton.png ganinsertaddialog.png

Article_PDF  = gan_manual.pdf

EXTRA_DIST=$(Article_TEXS) $(Article_FIGS)

noinst_DATA=$(Article_PDF)


$(Article_PDF) : $(Article_TEXS) $(Article_FIGS)
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual
	$(PDFLATEX) gan_manual

CLEANFILES = *.log *.aux *.toc *.bbl *.blg

DWSPDFS.zip : $(pkgdata_DATA)
	zip DWSPDFS.zip $(pkgdata_DATA)


<template>
	<div class="rmc_criteria_form_authority">
		<input v-if="selectedOp != 'AUTHORITY'" :id="id + '_id_0'" :name="id + '[]'"  type="hidden" v-model="searchValue">
		<input v-else :id="id + '_id_0'" :name="id + '[]'" type="hidden" :value="searchValueId">
		
		<div :id="'d' + id + '_lib_' + index" class="ajax_completion" ></div>
		<operators v-if="criteria.QUERIES" 
			:fieldId="criteria.FIELD_ID" 
			:index="index" 
			:queries="criteria.QUERIES" 
			:selected="selectedOp" 
			@changeOp=" e => changeOperator(e)"></operators>
		<div class="rmc_search_authority_container">
			<input
				:id="id + '_lib_' + index"
				:name="name"
				class="rmc_search_authority rmc_search_txt"
				type="text"
				autocomplete="off"
				:list="id + '_lib_' + index + '_datalist'"
				v-model="searchValue"
				@input.prevent="updateDataList"
				@keydown.down.prevent="increaseIndex"
				@keydown.up.prevent="decreaseIndex"
				@keydown.tab.exact.prevent="increaseIndex"
				@keydown.shift.tab.prevent="decreaseIndex"
				@keydown.esc="hideDatalist(true)"
				@keydown.enter="handleEnter">
				<!-- L'event blur provoque des problemes avec le click gauche enfonc� -->
				<!-- @blur="hideDatalist(true)">-->
	
			<ul v-if="dataListDisplayed" :id="id + '_lib_' + index + '_datalist'" class="rmc_datalist" role="listbox">
				<li v-for="(element, index) in dataList" :key="index"
					:class="`rmc_datalist_option ${index == dataListIndex ? 'rmc_datalist_option_active' : ''}`" 
					:data-entity_id="element.value"
					@click.self="selectElement(index); hideDatalist(false)"
					:aria-selected="index == dataListIndex"
					role="option">
	
					<div 
						:title="element.label" 
						class="rmc_datalist_label"
						@click.self="selectElement(index); hideDatalist(false)">
	
						{{ element.label }}
					</div>
				</li>
			</ul>
		</div>

        <fieldvars :fields="criteria.VAR" :fieldId="criteria.FIELD_ID" :index="index" :searchData="searchData"/>
    </div>
</template>
<script>
import fieldvars from "./fieldvars.vue";
import operators from "./operators.vue";

export default {
	name: "criteriaFormAuthority",
	props : ['criteria', 'searchData', 'index', 'showfieldvars'],
	data: function () {
		return {
			selectorValue: "",
			searchValue: "",
			dataList: [],
			dataListIndex: -1,
			dataListDisplayed: false,
			selectedOp: 'AUTHORITY',
	        searchValueId: ""
		}
	},
	components : {
	    fieldvars,
	    operators,
	},
	created : function() {
    	if ("undefined" !== typeof this.searchData[this.index] && (this.searchData[this.index].SEARCH == this.criteria.FIELD_ID)) {
	    	if(this.searchData[this.index] && this.searchData[this.index].OP){
	            for (var i = 0; i < this.criteria.QUERIES.length; i++) {
	                var operator = this.criteria.QUERIES[i];
	                if (this.searchData[this.index].OP == operator['OPERATOR']) {
	                	this.selectedOp = this.searchData[this.index].OP;
	                }
	            }
	    	}
		}
       	if(this.searchData[this.index] && this.searchData[this.index].FIELD){
	       	if(this.searchData[this.index] && this.searchData[this.index].FIELDLIB){
	       		this.searchValue = this.searchData[this.index].FIELDLIB[0];
	       	} else {
	       		this.searchValue = this.searchData[this.index].FIELD[0];
	       	}
	       	if(this.selectedOp == 'AUTHORITY'){
	       		this.searchValueId = this.searchData[this.index].FIELD[0];
	       	}
       	}

		this.initListeners();
	},
	computed: {
        name: function() {
            return `field_${this.index}_${this.criteria.FIELD_ID}_lib[]`;
        },
        autfield: function() {
        	return `field_${this.index}_${this.criteria.FIELD_ID}_id_0`;
        },
        autid: function() {
        	return `field_${this.index}_${this.criteria.FIELD_ID}_id_0`;
        },
        id: function() {
        	return `field_${this.index}_${this.criteria.FIELD_ID}`;
        },
        opName: function() {
        	return `op_${this.index}_${this.criteria.FIELD_ID}`;
        },
    },
	mounted: function() {
		this.authoritiesAjaxParse(this.criteria.INPUT_TYPE);
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },
	methods: {
		/**
		 * Parse le DOM � l'aide d'une fonction AJAX.
		 * @returns {void}
		 */
		authoritiesAjaxParse() {
			ajax_parse_dom();
		},

		/**
		 * Initialise les �couteurs d'�v�nements pour le formulaire.
		 * Ecoute l'�v�nement 'beforeSubmit' et met � jour l'op�rateur s�lectionn�.
		 * @returns {void}
		 */
		initListeners() {
			this.$root.$on("beforeSubmit", () => {
				let input = document.getElementById(this.id+'_id_0');
				if(input != null) {
					if(input.value == ""){
						//Si on n'a pas recupere l'id de l'autorite
						this.selectedOp = "BOOLEAN";
					}
				}
			})
		},

		/**
		 * S�lectionne l'�l�ment dans la liste.
		 * @param {number} index - L'index de l'�l�ment � s�lectionner. Par d�faut, -1 (aucun).
		 * @returns {void}
		 */
		selectElement(index = -1) {
			// Si un index est fourni (diff�rent de -1), met � jour l'index de la datalist
			if (index !== -1) {
				this.dataListIndex = index;
			}

			const selectedElement = this.dataList[this.dataListIndex];

			// V�rifie si l'�l�ment s�lectionn� existe
			if (selectedElement && selectedElement.value) {

				// Met � jour les champs avec les informations de l'�l�ment s�lectionn�
				this.$set(this, "selectedOp", 'AUTHORITY');
				this.$set(this, "searchValueId", selectedElement.value);
				this.$set(this, "searchValue", selectedElement.label);
			}
		},

		/**
		 * Met � jour la liste de donn�es en effectuant une requ�te AJAX.
		 * @returns {void}
		 */
		updateDataList() {
			// V�rifie si l'op�rateur s�lectionn� est 'AUTHORITY'
			if (this.selectedOp !== 'AUTHORITY') {
				return;
			}

			// Utilise la m�thode delay (debounce) pour limiter la fr�quence des requ�tes
			this.delay(() => {

				// Cr�ation d'un FormData pour envoyer les donn�es via AJAX
				const formData = new FormData();
				
				// Ajout des � la requ�te
				formData.append("handleAs", "json");
				formData.append("completion", this.criteria.INPUT_OPTIONS.AJAX);
				formData.append("autexclude", "");
				formData.append("param1", "");
				formData.append("param2", 1);
				formData.append("rmc_responsive", 1);
				if (this.criteria.LINKFIELD) {
					formData.append("linkfield", this.getLinkField());
				}
				
				// R�cup�re la valeur saisie, si vide utilise * pour rechercher tous les r�sultats
				const data = this.searchValue.trim() || "*";
				formData.append("datas", data);
				
				// Effectue la requ�te fetch avec les donn�es
				fetch("./ajax_selector.php", {
					method: 'POST',
					body: formData
				})
				.then(response => {
					// Si la r�ponse est valide, on la parse en JSON et on met � jour la dataList
					if (response.ok) {
						return response.json();
					} else {
						throw new Error("Erreur lors de la requ�te AJAX");
					}
				})
				.then(result => {
					// Met � jour la liste des suggestions avec les r�sultats renvoy�s par le serveur
					this.setDatalist(result);
				})
				.catch(error => {
					console.error("Erreur AJAX:", error.message);
				});
			}, 600); // D�lai en ms
		},

		/**
		 * Limite la fr�quence d'ex�cution d'une fonction.
		 * @param {Function} func - La fonction � ex�cuter apr�s le d�lai.
		 * @param {number} wait - Le d�lai en ms.
		 * @returns {void}
		 */
		delay(func, wait) {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(func, wait);
        },

		/**
		 * Met � jour la liste de suggestions avec les donn�es.
		 * @param {Array} data - La nouvelle liste de donn�es.
		 * @returns {void}
		 */
		setDatalist(data) {
			// Met � jour la liste de suggestions
			this.dataList = data;
			this.dataListIndex = -1;

			// Si la liste de suggestions contient des �l�ments, affiche la datalist
			if(this.dataList.length > 0) {
				this.displayDatalist();
				return;
			}

			// Sinon, masque la datalist
			this.hideDatalist();
		},

		/**
		 * Incr�mente l'index de l'�l�ment s�lectionn� dans la datalist.
		 * @returns {void}
		 */
		increaseIndex() {
			// V�rifie si la liste est vide ou non affich�e
			if (this.dataList.length === 0 || !this.dataListDisplayed) {

				// Met � jour la datalist si n�cessaire
				this.updateDataList();
				return;
			}

			// V�rifie que l'index actuel ne d�passe pas la longueur de la liste
			if (this.dataListIndex + 1 < this.dataList.length) {
				this.dataListIndex++;

				// Affiche et met � jour l'�l�ment s�lectionn�
				this.displayDatalist();
				this.updateFocus();
				this.selectElement();
			}
		},

		/**
		 * D�cr�mente l'index de l'�l�ment s�lectionn� dans la datalist.
		 * @returns {void}
		 */
		decreaseIndex() {
			// V�rifie si la liste est vide, si l'index est d�j� � 0 ou si la liste est cach�e
			if (this.dataList.length === 0 || this.dataListIndex === 0 || !this.dataListDisplayed) {
				return;
			}

			this.dataListIndex--;

			// Affiche et met � jour l'�l�ment s�lectionn�
			this.displayDatalist();
			this.updateFocus();
			this.selectElement();
		},

		/**
		 * R�initialise l'index de la liste de donn�es � -1.
		 * @returns {void}
		 */
		resetIndex() {
			this.dataListIndex = -1;
		},

		/**
		 * Affiche la liste des suggestions.
		 * @returns {void}
		 */
		displayDatalist() {
			this.dataListDisplayed = true;
		},

		/**
		 * G�re l'�v�nement d'appui sur la touche Entr�e.
		 * S�lectionne l'�l�ment et masque la liste de donn�es.
		 * @param {Event} event
		 * @returns {void}
		 */
		handleEnter(event) {
			// Si la liste des donn�es n'est pas affich�e et qu'aucun �l�ment n'est s�lectionn�
			if (!this.dataListDisplayed && this.dataListIndex === -1) {
				return;
			}

			// Emp�che le comportement par d�faut du navigateur
			event.preventDefault();

			// S�lectionne l'�l�ment actuel
			this.selectElement();

			// Masque la datalist
			this.hideDatalist();
		},

		/**
		 * Masque la liste de donn�es.
		 * Optionnellement, attend un d�lai avant de la masquer.
		 * @param {boolean} cooldown
		 * @returns {void}
		 */
		hideDatalist(cooldown = false) {
			// Si on a un d�lai, on attend un peu avant de masquer la datalist
			if (cooldown) {
				setTimeout(() => {
					// Masque la datalist
					this.dataListDisplayed = false;
				}, 140);
			} else {
				// Masque imm�diatement la datalist
				this.dataListDisplayed = false;
			}

			// R�initialise l'index de s�lection
			this.resetIndex();
		},

		/**
		 * Met � jour le focus sur l'�l�ment actuellement s�lectionn� dans la liste de donn�es.
		 * @returns {void}
		 */
		updateFocus() {
			// Si la liste n'est pas affich�e
			if (!this.dataListDisplayed) {
				return;
			}

			const customDatalist = document.querySelector('ul.rmc_datalist');
			
			// S'assure que la datalist existe avant de continuer
			if (!customDatalist) {
				return;
			}

			const listItems = customDatalist.querySelectorAll('li.rmc_datalist_option');

			listItems.forEach((item, index) => {
				if (index === this.dataListIndex) {
					// Scroll pour rendre visible l'�l�ment actif
					item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
				}
			});
		},

		/**
		 * G�re les clics en dehors de la liste de donn�es pour masquer la liste.
		 * @param {Event} event
		 * @returns {void}
		 */
		handleClickOutside(event) {
			// R�cup�re la datalist et l'input dans le composant courant
			const datalist = this.$el.querySelector('.rmc_datalist');
			const input = this.$el.querySelector('input.rmc_search_authority');

			// V�rifie si la datalist et l'input existent
			if (datalist && input) {
				// V�rifie si le clic a eu lieu en dehors de la datalist et de l'input
				const isClickOutside = !datalist.contains(event.target) && !input.contains(event.target);

				// Si le clic est en dehors on cache la datalist
				if (isClickOutside) {
					this.hideDatalist();
				}
			}
		},
		 
		/**
		 * methode appelee par le composant enfant pour mettre a jour l'operateur selectionne.
		 * @param array data
		 * @returns {void}
		 */
		changeOperator(data) {
			if(data[1] == this.index){
				this.selectedOp = data[0];
			}
		},
		
		/**
		 * recupere le champ lie
		 * @returns string
		 */
		getLinkField() {
        	if (this.criteria.LINKFIELD) {
        		let linffieldNode = document.getElementById(`fieldvar_${this.index}_${this.criteria.FIELD_ID}_${this.criteria.LINKFIELD}_0`);
        		if (linffieldNode && linffieldNode.value) {
        			return linffieldNode.value;
        		}
        	}
        	return "";
		}
	}
}
</script>